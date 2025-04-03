<section {{ $attributes->class(['infobox py-10']) }}>
    <div class="container">
        <div class="flex justify-center">
            <div class="w-full max-w-full">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-10 flex-wrap justify-between">
                    @foreach($blocks as $block)
                            <div class="group min-h-[90px] h-full w-full px-5 lg:px-10 py-6 border border-[#EDECEC] hover:shadow-lg rounded-md flex flex-row gap-6 lg:gap-8 items-center justify-center transition-all duration-300">
                                @if ($block['image'])
                                    <div class="flex-shrink-0">
                                        <img src="{{ $storageUrl . $block['image'] }}"
                                             class="w-[40px] md:w-[50px] max-h-11 group-hover:scale-110 transition-all duration-300"
                                             alt="icon">
                                    </div>
                                @endif
                                <div class="flex-1">
                                    @if ($block['heading'])
                                        <h4 class="text-lg sm:text-base md:text-lg leading-6 mb-1 group-hover:text-secondary transition-all duration-300">{{ $block['heading'] }}</h4>
                                    @endif
                                    @if($block['text'])
                                        <div
                                            class="sm:text-[12px] md:text-[15px] text-[#666666] leading-tight">{!! $block['text'] !!}</div>
                                    @endif

                                    @if ($block['link']['text'])
                                        <div>
                                            <a href="{{ $block['link']['url'] }}" target="{{ $block['link']['target'] }}"
                                               class="primary-btn min-w-[80px] my-3">{{ $block['link']['text'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
