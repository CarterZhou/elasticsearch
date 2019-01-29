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
     * @var string
     */
    protected $historicalClusterUrl;
    /**
     * @var array
     */
    protected $response;
    /**
     * @var int
     */
    protected $size = 10;
    /**
     * @var int
     */
    protected $total = 0;
    /**
     * @var int
     */
    protected $from = 0;
    /**
     * @var int
     */
    protected $currentRound = 1;
    /**
     * datetime string in ISO 8601 format, example: 2019-01-01T00:00:00Z
     *
     * @var string
     */
    protected $startDatetime;
    /**
     * datetime string in ISO 8601 format, example: 2019-01-01T00:00:00Z
     *
     * @var
     */
    protected $endDatetime;

    public function __construct()
    {
        $this->host = config('elasticsearch.host');
        $this->version = config('elasticsearch.version');
        $this->username = config('elasticsearch.username');
        $this->password = config('elasticsearch.password');
    }

    public function reset()
    {
        $this->total = 0;
        $this->currentRound = 1;
        $this->from = 0;
        $this->response = [];
    }

    /**
     * @param string $url
     * @param array $payload
     * @return Elasticsearch
     */
    public function search($url, array $payload)
    {
        $this->response = $this->curl("$url/_search", $payload);

        if (isset($this->response['hits'])) {
            $this->total = $this->response['hits']['total'];
            $this->currentRound++;
            $this->from = $this->size * ($this->currentRound - 1);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDocuments()
    {
        return $this->total > 0;
    }

    /**
     * @return bool
     */
    public function hasMoreDocuments()
    {
        return $this->from < $this->total;
    }

    /**
     * @param string $url
     * @param array $payload
     * @return array
     */
    protected function curl($url, $payload)
    {
        $payload = $this->appendSize($payload, $this->size);
        $payload = $this->appendFrom($payload, $this->from);
        $payload = $this->appendDatetimeRange($payload);
        $payload = json_encode($payload);

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

    /**
     * @param array $payload
     * @return array
     */
    protected function appendDatetimeRange($payload)
    {
        if (strlen($this->startDatetime) && strlen($this->endDatetime)) {
            $payload['query']['bool']['must'][] = [
                'range' => [
                    '@timestamp' => [
                        'gte' => $this->startDatetime,
                        'lte' => $this->endDatetime
                    ]
                ]
            ];
        }

        return $payload;
    }

    /**
     * @param array $payload
     * @param int $size
     * @return array
     */
    protected function appendSize($payload, $size)
    {
        if (!isset($payload['size'])) {
            $payload['size'] = 0;
        }
        $payload['size'] = $size;
        return $payload;
    }

    /**
     * @param array $payload
     * @param int $from
     * @return array
     */
    protected function appendFrom($payload, $from)
    {
        if (!isset($payload['from'])) {
            $payload['from'] = 0;
        }
        $payload['from'] = $from;
        return $payload;
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
     * @return string
     */
    public function getHistoricalClusterUrl()
    {
        return $this->historicalClusterUrl;
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
     * Because start datetime and end datetime can be across 2 days,
     * here maximum two indices will be returned.
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
}