{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  <a id="comment" href="#commentscrolldown">Comment</a>
  <a id="bookmark" onclick="toggleBookmark('{$cookieName}', '{$photo['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}')">Bookmark</a>
  <a id="like" href="{$photo['likeURL']}">{if $photo['liked']}Unlike{else}Like{/if}</a>  
</div>

<div class="photo">
  <img src="{$photo['img']['src']}" />
</div>

<div class="nonfocal">
  <div class="message">{$photo['message']}</div>
  <div class="smallprint">
    Uploaded {$photo['when']['delta']} by 
    <a class="author" href="{$photo['author']['url']}">{$photo['author']['name']}</a>
  </div>
</div>

{foreach $photo['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    {if $comment['removeURL']}
      <a class="removeLink" href="{$comment['removeURL']}">X</a>
    {/if}
    &ldquo;{$comment['message']}&rdquo; 
    <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['delta']}
    </span>
  {/capture}
  {$photo['comments'][$i]['title'] = $title}
{/foreach}

{capture name="addCommentHTML" assign="addCommentHTML"}
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    <textarea rows="2" name="message" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="id" value="{$photo['id']|escape:'url'}" />
    <input type="hidden" name="action" value="add" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addComment = array()}
{$addComment['title'] = $addCommentHTML}
{$photo['comments'][] = $addComment}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$photo['comments']}


{include file="findInclude:common/templates/footer.tpl"}
