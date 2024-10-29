<div class="container pb-14">
    <h2 class="text-2xl uppercase mb-3 text-center">{{ $data['title'] }}</h2>
    <!-- swiper -->
    <div class="swiper brandSwiper">
        <div class="swiper-wrapper">
            <!-- Slides -->
            @foreach(array_reverse($data['attachments']) as $attachment)
            <div class="swiper-slide">
                <div>
                    <img loading="lazy" src="/storage/{{ ($attachment) }}" alt="brand">
                </div>
            </div>
            @endforeach

        </div>
    </div>
</div>
