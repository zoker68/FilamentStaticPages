<div class="container pb-10">
    <div class="w-[1000px]" x-data="accordianModules">
        <template x-for="(acc,accIndex) in accordians">
            <div>
                <h4 class="text-2xl mt-4 mb-4" x-text="acc.section"></h4>
                <div class="border rounded">
                    <template x-for="(faq, faqIndex) in acc.faqs">
                        <div class="accordion-item">
                            <div @click="toggleFaq(accIndex, faqIndex)"
                                 :class="faq.isOpen ? 'text-primary !font-normal' : ''"
                                 class="border-b px-5 py-4 cursor-pointer flex justify-between w-full">
                                <h2 x-text="faq.question"></h2>
                                <span :class="faq.isOpen ? 'text-primary rotate-180' : ''"
                                      class="transition duration-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                             viewBox="0 0 24 24">
                                            <path fill="currentColor"
                                                  d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6l-6-6l1.41-1.41z"/></svg>
                                    </span>
                            </div>
                            <div x-show="faq.isOpen" x-text="faq.answer" class="accordion-body border-b px-4 py-5"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
@push('scripts')
    <script>
        const accordianModules = {
            accordians: [
                @foreach($categories as $category)
                {
                    section: '{{ $category['title'] }}',
                    faqs: [
                        @foreach($category['questions'] as $question)
                        {
                            question: '{{ $question['question'] }}',
                            answer: '{{ $question['answer'] }}',
                            isOpen: {{ $loop->first && $loop->parent->first ? 'true' : 'false' }}
                        },
                        @endforeach
                    ]
                },
                @endforeach
            ],

            toggleFaq(accIndex, faqIndex) {
                if (this.accordians[accIndex].faqs[faqIndex].isOpen) {
                    this.accordians[accIndex].faqs[faqIndex].isOpen = false
                } else {
                    this.accordians.forEach(acc => {
                        acc.faqs.forEach(faq => faq.isOpen = false)
                    })
                    this.accordians[accIndex].faqs[faqIndex].isOpen = true
                }
            }

        }
    </script>
@endpush
