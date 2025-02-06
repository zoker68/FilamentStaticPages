<div>
    <div class="w-full flex items-center">
        <!-- Slider main container -->
        <div class="swiper banner-swiper w-full">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                @foreach($slides as $slide)
                    <div class="swiper-slide flex items-center bg-no-repeat bg-cover bg-center min-h-[520px]"
                         style="background-image: url({{ $storageUrl . $slide['image'] }});">
                        <div class="container">
                            <div class="lg:flex flex-wrap">
                                <div class="w-full lg:w-1/2">
                                    <div>
                                        @if($slide['heading'] ?? false)
                                        <h1
                                            class="text-[38px] md:text-[56px] lg:text-5xl xl:text-[56px] leading-[42px] md:leading-[64px] lg:leading-[48px] xl:leading-[64px] font-medium mb-2 sm:mb-4 text-secondary">
                                            {{ $slide['heading'] }}
                                        </h1>
                                        @endif
                                        @if($slide['text'] ?? false)
                                        <p class="text-secondary text-base mb-2 sm:mb-4">
                                            {!! $slide['text'] !!}
                                        </p>
                                        @endif
                                        @if($slide['button'] ?? false)
                                        <div class="mt-[30px] md:mt-[40px]">
                                            <a class="primary-btn py-2.5" href="{{ $slide['link'] }}" target="{{ $slide['target'] }}" tabindex="-1">{{ $slide['button'] }}</a>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
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
