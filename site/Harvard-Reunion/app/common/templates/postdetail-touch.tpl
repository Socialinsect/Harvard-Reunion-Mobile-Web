{extends file="findExtends:common/templates/postdetail.tpl"}

{block name="postNavigation"}
  {if $post['prevURL']}
    <a class="postControl" id="prev" href="{$post['prevURL']}"><div></div></a>
  {/if}
  <a class="postControl" id="comment" href="#commentscrolldown"><div></div></a>
  <a class="postControl {$bookmarkStatus}" id="bookmark" href="{$bookmarkURL}"><div></div></a>
  <a class="postControl{if $post['liked']} liked{/if}" id="like" href="{$post['likeURL']}"><div></div></a>
  {if $post['nextURL']}
    <a class="postControl" id="next" href="{$post['nextURL']}"><div></div></a>
  {/if}
{/block}
