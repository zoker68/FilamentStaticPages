<div class="container">
    <div class="w-full flex items-center">
        <!-- Slider main container -->
        <div class="swiper banner-swiper w-full">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                @foreach($slides as $slide)
                    <div class="swiper-slide">
                        @if(($slide['link'] ?? false) && !($slide['button'] ?? false))
                            <a href="{{ $slide['link'] }}" target="{{ $slide['target'] }}">
                                @endif
                                <div class="swiper-slide-inner swiper-lazy" data-background="{{ $storageUrl . $slide['image'] }}">
                                     {{--style="background-image: url({{ $storageUrl . $slide['image'] }});">--}}
                                    @if(($slide['heading'] ?? false) || ($slide['text'] ?? false) || ($slide['button'] ?? false))
                                        <div class="w-full lg:w-1/2">
                                            @if($slide['heading'] ?? false)
                                                <h2>{{ $slide['heading'] }}</h2>
                                            @endif
                                            @if($slide['text'] ?? false)
                                                <p>{!! $slide['text'] !!}</p>
                                            @endif
                                            @if($slide['button'] ?? false)
                                                <div class="mt-[30px] md:mt-[40px]">
                                                    <a href="{{ $slide['link'] }}" target="{{ $slide['target'] }}"
                                                       tabindex="-1" class="btn btn-primary">{{ $slide['button'] }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if(($slide['link'] ?? false) && !($slide['button'] ?? false))
                            </a>
                        @endif
                    </div>
                @endforeach

            </div>

            <!-- If we need navigation buttons -->
            <div class="swiper-button-prev hidden xl:block collection-banner"></div>
            <div class="swiper-button-next hidden xl:block collection-banner"></div>

            <!-- If we need pagination -->
            <div class="swiper-pagination"></div>
        </div>
    </div>
</div>
