{extends file="findExtends:modules/{$moduleID}/templates/detail.tpl"}

{block name="bookmark"}
  <p id="bookmark">
    Bookmark
    {if $attending}
      <span class="fineprint"> (registered)</span>
    {else}
      <span class="fineprint"> (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)</span>
    {/if}
  </p>
{/block}

{block name="checkinLabel"}
  <img src="/common/images/button-foursquare.gif" /> 
{/block}
