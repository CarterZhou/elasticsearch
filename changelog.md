# Changelog

## Version 1.0.5
### Added
- Methods ```aggreate```, ```groupBy```, ```most``` and ```least``` for setting terms aggregation query.
- A ```getBucketAggregation``` method for getting aggregation result.
- A ```wildcard``` method for setting wildcard query.

## Version 1.0.4
### Updated
- Rename method ```must``` to ```match```
- Fix ```search```

## Version 1.0.3
### Added
- A ```raw``` method that enables user to search through json formatted payload directly.
- A ```getTop``` method that let user to get "terms" aggregation result.

## Version 1.0.2
### Updated
- Rename class ```CarterZhou\Elasticsearch\Elasticsearch``` to ```CarterZhou\Elasticsearch\Client```

## Version 1.0.1
### Added
- A chainable method ```must``` whereby user can add filter conditions.
- A ````scroll```` method whereby user can loop through large result set.
### Updated
- The fundamental ```curl``` method which curl functions to send and receive requests from Elasticsearch.

## Version 1.0.0
### Added
- Some fluent APIs with which user can communicate with Elasticsearch.