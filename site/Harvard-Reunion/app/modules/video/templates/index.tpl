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
          All Videos
        {elseif $view == 'mine'}
          My Videos
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

  {if count($videos)}
    <ul class="results">
      {foreach $videos as $video}
        <li class="videoListing">
          <a href="{$video['url']}">
            <div class="thumbnail"><img src="{$video['thumbnail']}" /></div>
            <div class="message">{$video['message']}</div>
            <div class="smallprint">
              Uploaded by {$video['author']['name']}
              <br/>
              {$video['when']['shortDelta']}
            </div>
          </a>
        </li>
      {/foreach}
    </ul>
  {else}
      <div class="nonfocal">
      {if $currentView == 'all'}
        There are no videos in this Facebook group.
      {elseif $currentView == 'mine'}
        You have not uploaded any videos to this Facebook group.
      {elseif $currentView == 'bookmarked'}
        You have not bookmarked any videos.
      {/if}
    </div>
  {/if}

  <div class="nonfocal">
    <span class="smallprint">
      Signed into Facebook as {$user} (<a href="{$switchUserURL}">change</a>)
    </span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
