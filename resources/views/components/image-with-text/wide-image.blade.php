<div {{ $attributes->class(['container pb-14']) }}>
    <div class="sm:flex gap-6">
        @foreach($blocks as $slide)
        <div class="w-full sm:w-1/{{ count($blocks) }} roudned-md">
            <div
                class="flex flex-col-reverse lg:flex-row gap-4 lg:gap-0 lg:items-center justify-between px-8 py-6 border border-[#EDECEC] bg-[#f3f3f3] rounded-md">
                <div class="text-center lg:text-left">
                    <h3 class="text-lg leading-4 mb-2 text-black">{{ $slide['heading'] }}</h3>
                    <div class="text-[#666666] leading-tight mb-6">{!! $slide['text'] !!}</div>
                    <a href="{{ $slide['link']['url'] }}" target="{{ $slide['link']['target'] }}"  class="btn btn-primary !py-3">{{ $slide['link']['text'] }}</a>
                </div>
                <div class="flex justify-center">
                    <img src="{{ $storageUrl.$slide['image'] }}"
                         class="w-[200px] h-[150px] lg:h-[180px] object-contain flex-shrink-0 hover:scale-105 transition-all duration-300"
                         alt="product">
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
