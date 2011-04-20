{extends file="findExtends:common/templates/postdetail.tpl"}

{block name="postNavigation"}
  {if $post['prevURL']}
    <a class="postControl" href="{$post['prevURL']}"><div id="prev"></div></a>
  {/if}
  <a class="postControl" href="#commentscrolldown"><div id="comment"></div></a>
  <a class="postControl" href="{$bookmarkURL}"><div id="bookmark" class="{$bookmarkStatus}"></div></a>
  <a class="postControl" href="{$post['likeURL']}"><div id="like"{if $post['liked']} class="liked"{/if}></div></a>
  {if $post['nextURL']}
    <a class="postControl" href="{$post['nextURL']}"><div id="next"></div></a>
  {/if}
{/block}
