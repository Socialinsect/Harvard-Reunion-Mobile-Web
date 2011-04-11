{extends file="findExtends:modules/{$moduleID}/templates/detail.tpl"}

{block name="bookmark"}
  <p id="bookmark" class="{$bookmarkStatus}">
      Bookmark
      {if $registered}
        <span class="fineprint"> (registered)</span>
      {else}
        <span class="fineprint"> (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)</span>
      {/if}
  </p>
{/block}
