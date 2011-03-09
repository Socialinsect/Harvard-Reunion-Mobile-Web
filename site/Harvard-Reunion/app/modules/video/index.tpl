{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$title}</h2>
  <div class="smallprint">
    Signed in as {$user} (<a href="{$logoutURL}">change</a>)
  </div>
</div>

<ul class="results">
  {foreach $videos as $video}
    <li class="video">
      <a href="{$video['url']}">
        <img class="thumbnail" src="{$video['thumbnail']}" />
        <div class="author">{$video['author']['name']}</div>
        <div class="message">{$video['message']}</div>
        <div class="when smallprint">{$video['when']['delta']}</div>
      </a>
    </li>
  {/foreach}
</ul>

{include file="findInclude:common/footer.tpl"}
