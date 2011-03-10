{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$title}</h2>
  <div class="smallprint">
    Signed in as {$user} (<a href="{$logoutURL}">change</a>)
  </div>
</div>

<div class="videos">
  {foreach $videos as $video}
    <div class="video">
      <a href="{$video['url']}">
        <div class="wrapper">
          <div class="wrapper2">
            <img class="thumbnail" src="{$video['thumbnail']}" />
          </div>
        </div>
        <div class="when">{$video['when']['delta']}</div>
      </a>
    </div>
  {/foreach}
</div>

{include file="findInclude:common/footer.tpl"}
