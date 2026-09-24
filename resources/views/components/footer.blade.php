@props(['year'])
<footer class="footer">
  <p class="footer__disclaimer">These are other people's websites, so do have a look yourself before you rely on one - us listing it isn't a promise or guarantee it's great for everyone.</p>
  <div class="footer__meta">
    <span>Want an email when we add something? <a href="/#suggest">Yes please</a></span>
    <span>&copy; {{ $year }} {{ config('site.name') }}</span>
  </div>
</footer>
