{include file="findInclude:common/templates/header.tpl"}

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
        All Videos
      {elseif $view == 'mine'}
        My Videos
      {elseif $view == 'bookmarked'}
        Bookmarks
      {/if}
      {if $currentView != $view}
        </a>
      {/if}
    {/foreach}
  </div>
  
  <ul class="results">
    {foreach $videos as $video}
      <li class="video">
        <a href="{$video['url']}">
          <div class="thumbnail"><img src="{$video['thumbnail']}" /></div>
          <div class="message">{$video['message']}</div>
          <div class="smallprint">
            Uploaded by {$video['author']['name']}
            <br/>
            {$video['when']['delta']}
          </div>
        </a>
      </li>
    {/foreach}
  </ul>
  
  <div class="nonfocal">
    <span class="smallprint">
      Signed in as {$user} (<a href="{$switchUserURL}">change</a>)
    </span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
