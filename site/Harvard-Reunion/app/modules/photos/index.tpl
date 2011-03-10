{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$title}</h2>
  <div class="smallprint">
    Signed in as {$user} (<a href="{$logoutURL}">change</a>)
  </div>
</div>

<ul class="results">
  {foreach $photos as $photo}
    <li class="photo">
      <a href="{$photo['url']}">
        <img class="thumbnail" src="{$photo['thumbnail']}" />
        <div class="author">{$photo['author']['name']}</div>
        <div class="message">{$photo['message']}</div>
        <div class="when smallprint">{$photo['when']['delta']}</div>
      </a>
    </li>
  {/foreach}
</ul>

{include file="findInclude:common/footer.tpl"}
