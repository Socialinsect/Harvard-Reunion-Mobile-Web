{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$title}</h2>
  <div class="smallprint">
    Signed in as {$user} (<a href="{$logoutURL}">change</a>)
  </div>
</div>

<div class="photos">
  {foreach $photos as $photo}
    <div class="photo">
      <a href="{$photo['url']}">
        <div class="wrapper">
          <img class="thumbnail" src="{$photo['thumbnail']}" />
        </div>
        <div class="when">{$photo['when']['delta']}</div>
      </a>
    </div>
  {/foreach}
</div>

{include file="findInclude:common/footer.tpl"}
