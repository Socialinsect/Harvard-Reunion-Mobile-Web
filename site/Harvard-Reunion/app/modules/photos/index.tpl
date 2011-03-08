{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$title}</h2>
  <div class="smallprint">
    Logged into Facebook as {$user} | <a href="{$logoutURL}">Sign in as another user</a>
  </div>
</div>

<ul class="results">
  {foreach $photos as $photo}
    <li>
      <img src="{$photo['thumbnail']}" /><br>
      {$photo['title']}
    </li>
  {/foreach}
</ul>

{include file="findInclude:common/footer.tpl"}
