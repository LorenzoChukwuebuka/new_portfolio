<ol class="article-toc__links">
    @foreach ($toc as $heading)
        <li class="article-toc__level-{{ $heading['level'] }}"><a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
    @endforeach
</ol>
