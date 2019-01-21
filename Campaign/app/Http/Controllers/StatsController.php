<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\CampaignBanner;
use Illuminate\Http\Request;
use App\Contracts\Remp\Stats;
use Remp\MultiArmedBandit\Lever;
use Remp\MultiArmedBandit\Machine;
use App\Contracts\StatsContract;
use App\Contracts\StatsHelper;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    private $statTypes = [
        "show" => [
            "label" => "Shows",
            "backgroundColor" => "#E63952",
        ],
        "click" => [
            "label" => "Clicks",
            "backgroundColor" => "#00C7DF",
        ],
        "commerce" => [
            "label" => "Conversions",
            "backgroundColor" => "#FFC34A",
        ],
    ];

    private $statsHelper;

    private $stats;

    public function __construct(StatsHelper $statsHelper, StatsContract $stats)
    {
        $this->statsHelper = $statsHelper;
        $this->stats = $stats;
    }

    private function getHistogramData(array $variantUuids, Carbon $from, Carbon $to, $chartWidth)
    {
        $parsedData = [];
        $labels = [];

        $interval = $this->calcInterval($from, $to, $chartWidth);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $data = $stats->count()
                ->forVariants($variantUuids)
                ->timeHistogram($interval)
                ->from($from)
                ->to($to);

            if ($type === 'commerce') {
                $data = $data->commerce('purchase');
            } else {
                $data = $data->events('banner', $type);
            }

            $result = $data->get();

            $histogramData = $result[0];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = Carbon::parse($histogramRow->time);

                $parsedData[$type][$date->toRfc3339String()] = $histogramRow->value;

                $labels[] = $date;
            }
        }

        $labels = array_unique($labels);

        usort($labels, function ($a, $b) {
            return $a > $b;
        });

        list($dataSets, $formattedLabels) = $this->formatDataForChart($parsedData, $labels);

        return [
            'dataSets' => $dataSets,
            'labels' => $formattedLabels,
        ];
    }

    public function getStats(Campaign $campaign, Request $request)
    {
        $from = Carbon::parse($request->get('from'), $request->input('tz'));
        $to = Carbon::parse($request->get('to'), $request->input('tz'));
        $chartWidth = $request->get('chartWidth');

        $campaignData = $this->statsHelper->campaignStats($campaign, $from, $to);
        $campaignData['histogram'] = $this->getHistogramData($campaign->variants_uuids, $from, $to, $chartWidth);

        $variantsData = [];
        foreach ($campaign->campaignBanners()->withTrashed()->get() as $variant) {
            $variantStats = $this->statsHelper->variantStats($variant, $from, $to);
            $variantStats['histogram'] = $this->getHistogramData([$variant->uuid], $from, $to, $chartWidth);
            $variantsData[$variant->id] = $variantStats;
        }

        // a/b test evaluation data
        foreach ($this->getVariantProbabilities($variantsData, "click_count") as $variantId => $probability) {
            $variantsData[$variantId]['click_probability'] = $probability;
        }
        foreach ($this->getVariantProbabilities($variantsData, "purchase_count") as $variantId => $probability) {
            $variantsData[$variantId]['purchase_probability'] = $probability;
        }

        return [
            'campaign' => $campaignData,
            'variants' => $variantsData,
        ];
    }

    public function campaignStats(Campaign $campaign, Request $request, Stats $stats)
    {
        $variantUuids = $campaign->campaignBanners()->withTrashed()->get()->map(function ($banner) {
            return $banner["uuid"];
        })->toArray();

        $data = [
            'click_count' => $this->campaignStatsCount($variantUuids, 'click', $stats, $request),
            'show_count' => $this->campaignStatsCount($variantUuids, 'show', $stats, $request),
            'payment_count' => $this->campaignPaymentStatsCount($variantUuids, 'payment', $stats, $request),
            'purchase_count' => $this->campaignPaymentStatsCount($variantUuids, 'purchase', $stats, $request),
            'purchase_sum' => $this->campaignPaymentStatsSum($variantUuids, 'purchase', $stats, $request),
            'histogram' => $this->campaignStatsHistogram($variantUuids, $stats, $request),
        ];

        return $this->addCalculatedValues($data);
    }

    public function variantStats(CampaignBanner $variant, Request $request, Stats $stats)
    {
        $data = [
            'click_count' => $this->variantStatsCount($variant, 'click', $stats, $request),
            'show_count' => $this->variantStatsCount($variant, 'show', $stats, $request),
            'payment_count' => $this->variantPaymentStatsCount($variant, 'payment', $stats, $request),
            'purchase_count' => $this->variantPaymentStatsCount($variant, 'purchase', $stats, $request),
            'purchase_sum' => $this->variantPaymentStatsSum($variant, 'purchase', $stats, $request),
            'histogram' => $this->variantStatsHistogram($variant, $stats, $request),
        ];

        return $this->addCalculatedValues($data);
    }

    public function addCalculatedValues($data)
    {
        $data['ctr'] = 0;
        $data['conversions'] = 0;

        // calculate ctr & conversions
        if ($data['show_count']->count) {
            if ($data['click_count']->count) {
                $data['ctr'] = ($data['click_count']->count / $data['show_count']->count) * 100;
            }

            if ($data['purchase_count']->count) {
                $data['conversions'] = ($data['purchase_count']->count / $data['show_count']->count) * 100;
            }
        }

        return $data;
    }

    public function getVariantProbabilities($variantsData, $conversionField)
    {
        $machine = new Machine(1000);
        $zeroStat = [];
        foreach ($variantsData as $variantId => $data) {
            if (!$data[$conversionField]->count) {
                $zeroStat[$variantId] = 0;
                continue;
            }
            $machine->addLever(new Lever($variantId, $data[$conversionField]->count, $data['show_count']->count));
        }
        return $machine->run() + $zeroStat;
    }

    protected function formatDataForChart($typesData, $labels)
    {
        $dataSets = [];

        foreach ($typesData as $type => $data) {
            $dataSet = [
                'label' => $this->statTypes[$type]['label'],
                'data' => [],
                'backgroundColor' => $this->statTypes[$type]['backgroundColor']
            ];

            foreach ($labels as $i => $label) {
                $labelStr = is_string($label) ? $label : $label->toRfc3339String();

                if (array_key_exists($labelStr, $data)) {
                    $dataSet['data'][] = $data[$labelStr];
                } else {
                    $dataSet['data'][] = 0;
                }

                $labels[$i] = $labelStr;
            }

            $dataSets[] = $dataSet;
        }

        return [
            $dataSets,
            $labels,
        ];
    }

    private function calcInterval(Carbon $from, Carbon $to, $chartWidth)
    {
        $numOfCols = intval($chartWidth / 40);

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $numOfCols;

        return intval($interval) . "s";
    }
}
