<?php

namespace App\Contracts;

use App\Campaign;
use App\CampaignBanner;
use Illuminate\Support\Carbon;

class StatsHelper
{
    private $stats;

    public function __construct(StatsContract $statsContract)
    {
        $this->stats = $statsContract;
    }

    public function campaignStats(Campaign $campaign, Carbon $from = null, Carbon $to = null)
    {
        $variantUuids = $campaign->variants_uuids;
        $data = [
            'click_count' => $this->campaignStatsCount($variantUuids, 'click', $from, $to),
            'show_count' => $this->campaignStatsCount($variantUuids, 'show', $from, $to),
            'payment_count' => $this->campaignPaymentStatsCount($variantUuids, 'payment', $from, $to),
            'purchase_count' => $this->campaignPaymentStatsCount($variantUuids, 'purchase', $from, $to),
            'purchase_sum' => $this->campaignPaymentStatsSum($variantUuids, 'purchase', $from, $to),
        ];

        return $this->addCalculatedValues($data);
    }

    public function variantStats(CampaignBanner $variant, Carbon $from = null, Carbon $to = null)
    {
        $data = [
            'click_count' => $this->variantStatsCount($variant, 'click', $from, $to),
            'show_count' => $this->variantStatsCount($variant, 'show', $from, $to),
            'payment_count' => $this->variantPaymentStatsCount($variant, 'payment', $from, $to),
            'purchase_count' => $this->variantPaymentStatsCount($variant, 'purchase', $from, $to),
            'purchase_sum' => $this->variantPaymentStatsSum($variant, 'purchase', $from, $to),
        ];

        return $this->addCalculatedValues($data);
    }

    private function addCalculatedValues($data)
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

    private function campaignStatsCount($variantUuids, $type, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->events('banner', $type)
            ->forVariants($variantUuids);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function campaignPaymentStatsCount($variantUuids, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->commerce($step)
            ->forVariants($variantUuids);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function campaignPaymentStatsSum($variantUuids, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->sum()
            ->commerce($step)
            ->forVariants($variantUuids);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function variantStatsCount(CampaignBanner $variant, $type, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->events('banner', $type)
            ->forVariant($variant->uuid);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function variantPaymentStatsCount(CampaignBanner $variant, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->commerce($step)
            ->forVariant($variant->uuid);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function variantPaymentStatsSum(CampaignBanner $variant, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->sum()
            ->commerce($step)
            ->forVariant($variant->uuid);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }
}