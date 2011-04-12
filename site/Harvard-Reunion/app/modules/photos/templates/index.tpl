{include file="findInclude:common/templates/header.tpl" scalable=false}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div id="navbar2">
    <div class="tabstrip threetabs">
      {foreach $views as $view => $url}
        {if !$url@first}<span class="tabstripDivider"> | </span>{/if}
        {if $currentView != $view}
          <a href="{$url}">
        {else}
          <span class="selected">
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
        {else}
          </span>
        {/if}
      {/foreach}
    </div>
  </div>
  
  {if count($photos)}
    <div class="photos">
      {foreach $photos as $photo}
        {block name="photo"}
          <div class="photo">
            <a href="{$photo['url']}">
              <div class="wrapper">
                <img class="thumbnail" src="{$photo['thumbnail']}" />
              </div>
              <div class="when">{$photo['when']['shortDelta']}</div>
            </a>
          </div>
        {/block}
      {/foreach}
    </div>
  {else}
    <div class="nonfocal">
      {if $currentView == 'all'}
        There are no viewable photos in this Facebook group.
      {elseif $currentView == 'mine'}
        You have not uploaded any photos to this Facebook group.
      {elseif $currentView == 'bookmarked'}
        You have not bookmarked any photos.
      {/if}
    </div>
  {/if}
  
  <div class="nonfocal">
    <span class="smallprint">Signed into Facebook as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
