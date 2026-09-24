<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SmsChartCard extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public $title,
        public $subtitle = null,
        public $buttonText = null,
        public $buttonLink = '#',
        public $chartId = 'chart',
        public $height = '220px'
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sms-chart-card');
    }
}
