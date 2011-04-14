{extends file="findExtends:modules/{$moduleID}/templates/detail.tpl"}

{block name="bookmark"}
  <p id="bookmark" class="{$bookmarkStatus}">
    Bookmark
    {if $registered}
      <span class="smallprint"> (registered)</span>
    {else}
      <span class="smallprint"> (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)</span>
    {/if}
  </p>
{/block}

{block name="checkinLabel"}
  <img src="/common/images/button-foursquare.gif" /> 
{/block}
