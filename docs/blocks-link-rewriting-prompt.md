# Prompt: make another package's blocks work with FSP cross-site transfer

Hand this to a session working on any package that registers its own FilamentStaticPages
blocks (via `BlocksComponentRegistry::register(...)`), so those blocks get **text translation**
and **internal-link rewriting** when an FSP page is copied between sites.

---

```
Задача: сделать так, чтобы у блоков этого пакета при переносе FSP-страницы между
сайтами работали переводы текста и переписывание внутренних ссылок.

Контекст: пакет zoker/filament-static-pages при копировании страницы обходит блоки
и по статическим свойствам блок-компонента определяет, какие поля переводить и в
каких переписывать ссылки. Блоки регистрируются через
BlocksComponentRegistry::register(...) и должны наследовать
Zoker\FilamentStaticPages\Classes\BlockComponent.

Для КАЖДОГО блок-компонента пакета:
1. Открой getSchema() и определи, как поля лежат в data (учти вложенность и
   repeater'ы — путь пишется дот-нотацией, * = каждый элемент репитера).
2. Объяви статические массивы (только нужные):
   - public static array $translatable — поля с человекочитаемым текстом
     (заголовки, подписи, тексты кнопок, alt). НЕ включай URL, id, enum,
     css-классы, телефоны/email, картинки.
   - public static array $links — поля с обычной URL-строкой
     (например 'link.url', 'slides.*.link', 'all_link_url').
   - public static array $htmlLinks — поля RichEditor/HTML, где внутри могут быть
     <a href> (перепишутся href-атрибуты).
   (Canonical URL в блоках НЕ задаётся — это render-time/SEO задача AlternateLinks.)
3. Блоки без ссылок/текста не трогай. Блоки, где ссылки хранятся как ссылки на
   страницы/маршруты и резолвятся на рендере, — тоже (как Breadcrumbs).
4. Синтаксис путей и правила — как у существующего $translatable; примеры и таблица
   есть в FilamentStaticPages/README.md (раздел «Cross-site link rewriting»).
5. Добавь тест: прогони Zoker\FilamentStaticPages\Services\BlockContentTranslator
   (со стаб-Translator) и BlockLinkRewriter (+ LinkRewriter, два Site: один без
   префикса, один с префиксом) над репрезентативными данными блоков; проверь, что
   нужные поля переведены/переписаны, а URL-адреса/id/контакты — нет.
   Ориентир: packages/zoker/shop/tests/Unit/View/Components/Blocks/ShopBlocksTransferTest.php
6. Прогони тесты и pint. Помни: правки в пакете zoker/* нужно донести в его
   upstream-репозиторий.
```
