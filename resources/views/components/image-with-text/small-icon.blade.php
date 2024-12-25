<section {{ $attributes->class(['py-14']) }}>
    <div class="container">
        <div class="flex justify-center">
            <div class="w-full xl:w-5/6 max-w-full xl:px-3">
                <div class="sm:flex justify-center">
                    @foreach($blocks as $block)
                        <div class="w-[270px] sm:w-1/3 max-w-full sm:pr-3 mb-3 sm:mb-0 mx-auto">
                            <div class="min-h-[90px] border border-primary rounded-sm flex items-center justify-center">
                                @if ($block['image'])
                                    <div class="mr-3 md:mr-6 flex-shrink-0">
                                        <img src="{{ $storageUrl . $block['image'] }}"
                                             class="w-[40px] md:w-[50px] max-h-11"
                                             alt="icon">
                                    </div>
                                @endif
                                <div>
                                    @if ($block['heading'])
                                        <h4 class="text-lg sm:text-base md:text-lg leading-6 mb-1">{{ $block['heading'] }}</h4>
                                    @endif
                                    @if($block['text'])
                                        <div
                                            class="sm:text-[10px] md:text-[13px] text-[#6B6B6B]">{!! $block['text'] !!}</div>
                                    @endif

                                    @if ($block['link']['text'])
                                        <div>
                                            <a href="{{ $block['link']['url'] }}" target="{{ $block['link']['target'] }}"
                                               class="primary-btn min-w-[80px] my-3">{{ $block['link']['text'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
