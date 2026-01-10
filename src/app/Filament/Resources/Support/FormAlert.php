<?php

namespace App\Filament\Support;

use Illuminate\Support\HtmlString;

class FormAlert
{
    public static function danger(string $message): HtmlString
    {
        return new HtmlString('
            <div
                class="flex items-center gap-2 p-3 mb-3 rounded-lg border"
                style="
                    background-color: rgb(254 226 226);
                    border-color: rgb(220 38 38);
                    box-shadow: 0 0 0 rgba(220,38,38,0);
                    animation: danger-glow 1.6s ease-in-out infinite;
                "
            >
                <svg
                    class="w-5 h-5 shrink-0"
                    style="color: rgb(220 38 38);"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856
                             c1.54 0 2.502-1.667 1.732-3
                             L13.732 4c-.77-1.333-2.694-1.333-3.464 0
                             L3.34 16c-.77-1.333.192 3 1.732 3z" />
                </svg>

                <span
                    style="
                        color: rgb(185 28 28);
                        font-weight: 700;
                        text-decoration: underline;
                    "
                >
                    ' . e($message) . '
                </span>
            </div>

            <style>
                @keyframes danger-glow {
                    0% {
                        box-shadow: 0 0 0 rgba(220,38,38,0.0);
                    }
                    50% {
                        box-shadow: 0 0 14px rgba(220,38,38,0.65);
                    }
                    100% {
                        box-shadow: 0 0 0 rgba(220,38,38,0.0);
                    }
                }
            </style>
        ');
    }
}
