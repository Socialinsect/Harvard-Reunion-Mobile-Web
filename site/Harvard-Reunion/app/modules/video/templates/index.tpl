{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  sorting goodness
</div>

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
        <div class="thumbnail"><img src="{$video['thumbnail']}" /></div>
        <div class="message">{$video['message']}</div>
        <div class="author smallprint">Uploaded by {$video['author']['name']}</div>
        <div class="when smallprint">{$video['when']['delta']}</div>
      </a>
    </li>
  {/foreach}
</ul>

{include file="findInclude:common/templates/footer.tpl"}
