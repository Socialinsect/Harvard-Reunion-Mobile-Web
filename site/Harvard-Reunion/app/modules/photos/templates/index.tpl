{include file="findInclude:common/templates/header.tpl" scalable=false}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div class="nonfocal" id="navbar2">
    {foreach $views as $view => $url}
      {if !$url@first}&nbsp;|&nbsp;{/if}
      {if $currentView != $view}
        <a href="{$url}">
      {/if}
      {if $view == 'all'}
        All Photos
      {elseif $view == 'mine'}
        My Photos
      {elseif $view == 'bookmarked'}
        Bookmarks
      {/if}
      {if $currentView != $view}
        </a>
      {/if}
    {/foreach}
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
  
  <div class="nonfocal">
    <span class="smallprint">Signed in as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
