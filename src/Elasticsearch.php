<?php

namespace CarterZhou\Elasticsearch;

use Carbon\Carbon;

class Elasticsearch
{
    /**
     * @var string
     */
    protected $version;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var array
     */
    protected $payload;
    /**
     * @var array
     */
    protected $response;
    /**
     * @var int
     */
    protected $total = 0;
    /**
     * @var int
     */
    protected $size = 10;
    /**
     * @var int
     */
    protected $from = 0;
    /**
     * @var int
     */
    protected $currentRound = 1;
    /**
     * @var string
     */
    protected $contextAliveTime = '1m';
    /**
     * @var string
     */
    protected $scrollId = '';
    /**
     *  A datetime string in ISO 8601 format, example: 2019-01-01T00:00:00Z.
     *
     * @var string
     */
    protected $startDatetime;
    /**
     * A datetime string in ISO 8601 format, example: 2019-01-01T00:00:00Z.
     *
     * @var string
     */
    protected $endDatetime;

    public function __construct()
    {
        $this->host = config('elasticsearch.host');
        $this->version = config('elasticsearch.version');
        $this->username = config('elasticsearch.username');
        $this->password = config('elasticsearch.password');
        $this->payload['query'] = [];
    }

    public function reset()
    {
        $this->total = 0;
        $this->currentRound = 1;
        $this->from = 0;
        $this->response = [];
        $this->payload = [];
    }

    /**
     * @param string $url
     * @return Elasticsearch
     */
    public function search($url)
    {
        $this->appendFrom()->appendDatetimeRange();
        $this->response = $this->curl("$url/_search");

        if (isset($this->response['hits'])) {
            $this->total = $this->response['hits']['total'];
            $this->currentRound++;
            $this->from = $this->size * ($this->currentRound - 1);
        }

        return $this;
    }

    /**
     * @param string $url
     * @return Elasticsearch
     */
    public function scroll($url)
    {
        if (strlen($this->scrollId)) {
            $this->response = $this->setScrollPayload()->curl($this->getHost() . '/_search/scroll');
        } else {
            $this->response = $this->appendSize()->curl("$url/_search?scroll=1m");
        }
        $this->scrollId = $this->response['_scroll_id'];

        return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return Elasticsearch
     */
    public function must($field, $value)
    {
        if (!isset($this->payload['query']['bool']['must'])) {
            $this->payload['query']['bool']['must'] = [];
        }
        $this->payload['query']['bool']['must'][] = [
            'match' => [
                $field => $value
            ]
        ];

        return $this;
    }

    /**
     * @return Elasticsearch
     */
    public function matchAll()
    {
        $this->payload['query'] = [
            'match_all' => (object)[]
        ];

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDocuments()
    {
        return count($this->response['hits']['hits']);
    }

    /**
     * @return bool
     */
    public function hasMoreDocuments()
    {
        return $this->from < $this->total;
    }

    /**
     * @return Elasticsearch
     */
    protected function appendDatetimeRange()
    {
        if (strlen($this->startDatetime) && strlen($this->endDatetime)) {
            $this->payload['query']['bool']['must'][] = [
                'range' => [
                    '@timestamp' => [
                        'gte' => $this->startDatetime,
                        'lte' => $this->endDatetime
                    ]
                ]
            ];
        }

        return $this;
    }

    /**
     * @return Elasticsearch
     */
    protected function appendSize()
    {
        $this->payload['size'] = $this->size;
        return $this;
    }

    /**
     * @return Elasticsearch
     */
    protected function appendFrom()
    {
        $this->payload['from'] = $this->from;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDocuments()
    {
        return $this->response['hits']['hits'];
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param int $size
     * @return Elasticsearch
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @param string $start
     * @param string $end
     * @return Elasticsearch
     */
    public function setDatetimeRange($start, $end)
    {
        $this->startDatetime = Carbon::parse($start)->toIso8601ZuluString();
        $this->endDatetime = Carbon::parse($end)->toIso8601ZuluString();
        return $this;
    }

    /**
     * A common strategy to index documents is grouping them by dates.
     * A typical index format would be like "logstash-2019.01.01".
     * This method is designed to work out which indices should be used upon searching.
     * Because start datetime and end datetime can be across 2 days, here maximum 2 indices will be returned.
     *
     * @return array
     */
    public function getIndices()
    {
        $indices[] = str_replace('-', '.', substr($this->startDatetime, 0, strpos($this->startDatetime, 'T')));

        $endDay = Carbon::parse($this->startDatetime)->startOfDay();
        $startDay = Carbon::parse($this->endDatetime)->startOfDay();
        if ($endDay->diffInDays($startDay) !== 0) {
            $indices[] = str_replace('-', '.', substr($this->endDatetime, 0, strpos($this->endDatetime, 'T')));
        }

        return $indices;
    }

    /**
     * @param string $url
     * @return array
     */
    protected function curl($url)
    {
        $payload = json_encode($this->payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "kbn-version: {$this->version}"]);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);

        return $response;
    }

    protected function setScrollPayload()
    {
        $this->payload = [
            'scroll' => $this->contextAliveTime,
            'scroll_id' => $this->scrollId
        ];
        return $this;
    }
}