<div class="nonfocal" id="navbar2">
  {if $post['prevURL']}<a id="prev" href="{$post['prevURL']}">Prev</a>{/if}
  <a id="comment" href="#commentscrolldown">Comment</a> | 
  <a id="bookmark" onclick="toggleBookmark('{$cookieName}', '{$post['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}')">Bookmark</a> | 
  <a id="like" href="{$post['likeURL']}">{if $post['liked']}Unlike{else}Like{/if}</a>  
  {if $post['nextURL']}<a id="next" href="{$post['nextURL']}">Next</a>{/if}
</div>

{$post['html']}

<div class="nonfocal">
  <div class="message">{$post['message']}</div>
  <div class="smallprint">
    Uploaded {$post['when']['delta']} by 
    <a class="author" href="{$post['author']['url']}">{$post['author']['name']}</a>
  </div>
</div>

{foreach $post['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    {* if $comment['removeURL']}
      <a class="removeLink" href="{$comment['removeURL']}">X</a>
    {/if *}
    &ldquo;{$comment['message']}&rdquo; 
    <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['delta']}
    </span>
  {/capture}
  {$post['comments'][$i]['title'] = $title}
{/foreach}

{capture name="addCommentHTML" assign="addCommentHTML"}
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    <textarea rows="2" name="message" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="id" value="{$post['id']|escape:'url'}" />
    <input type="hidden" name="view" value="{$currentView}" />
    <input type="hidden" name="action" value="add" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addComment = array()}
{$addComment['title'] = $addCommentHTML}
{$post['comments'][] = $addComment}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$post['comments']}
