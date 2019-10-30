<?php

namespace App\Model\Config;

class ConfigNames
{
    const AUTHOR_SEGMENTS_MIN_RATIO = 'author_segments_min_ratio';
    const AUTHOR_SEGMENTS_MIN_VIEWS = 'author_segments_min_views';
    const AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT = 'author_segments_min_average_timespent';
    const AUTHOR_SEGMENTS_DAYS_IN_PAST = 'author_segments_days_in_past';

    const CONVERSION_RATE_MULTIPLIER = 'conversion_rate_multiplier';
    const CONVERSION_RATE_DECIMAL_NUMBERS = 'conversion_rate_multiplier_decimals';
    const CONVERSIONS_COUNT_THRESHOLD_LOW =  'conversions_count_threshold_low';
    const CONVERSIONS_COUNT_THRESHOLD_MEDIUM =  'conversions_count_threshold_medium';
    const CONVERSIONS_COUNT_THRESHOLD_HIGH =  'conversions_count_threshold_high';
    const CONVERSION_RATE_THRESHOLD_LOW =  'conversion_rate_threshold_low';
    const CONVERSION_RATE_THRESHOLD_MEDIUM =  'conversion_rate_threshold_medium';
    const CONVERSION_RATE_THRESHOLD_HIGH =  'conversion_rate_threshold_high';

    const DASHBOARD_FRONTPAGE_REFERER =  'dashboard_frontpage_referer';

    const REFERER_MEDIUMS_SHOWN_AS_SINGLE_SOURCE =  'referer_mediums_shown_as_single_source';

    /**
     * Lists config options that can specified for token properties
     * @return array
     */
    public static function propertyConfigs(): array
    {
        return [
            self::CONVERSION_RATE_MULTIPLIER,
            self::CONVERSION_RATE_DECIMAL_NUMBERS,
            self::CONVERSIONS_COUNT_THRESHOLD_LOW,
            self::CONVERSIONS_COUNT_THRESHOLD_MEDIUM,
            self::CONVERSIONS_COUNT_THRESHOLD_HIGH,
            self::CONVERSION_RATE_THRESHOLD_LOW,
            self::CONVERSION_RATE_THRESHOLD_MEDIUM,
            self::CONVERSION_RATE_THRESHOLD_HIGH,
            self::DASHBOARD_FRONTPAGE_REFERER
        ];
    }
}
