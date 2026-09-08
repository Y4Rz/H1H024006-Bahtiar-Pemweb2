<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BadgeSks extends Component
{
    public function __construct(public int $sks) {}

    public function render(): View
    {
        return view('components.badge-sks');
    }
}