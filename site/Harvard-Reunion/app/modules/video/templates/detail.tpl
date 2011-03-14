{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  <a id="comment" href="#commentscrolldown">Comment</a>&nbsp;|&nbsp;
  <a id="bookmark" onclick="toggleBookmark('{$name}', '{$video['id']}', {$exdate}, '{$smarty.const.COOKIE_PATH}')">Bookmark</a>&nbsp;|&nbsp;
  <a id="like" href="{$video['likeURL']}">{if $video['liked']}Unlike{else}Like{/if}</a>  
</div>

{if $video['embedHTML']}
  <div class="video">
    {$video['embedHTML']}
  </div>
{else}
  <div class="nonfocal videoInfo">
    <h2>Video not available</h2>
  </div>
{/if}

<div class="nonfocal videoInfo">
  <div class="message">{$video['message']}</div>
  <div class="smallprint">
    Uploaded {$video['when']['delta']} by {$video['author']['name']}
  </div>
</div>

{foreach $video['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    {if $comment['removeURL']}
      <a class="removeLink" href="{$comment['removeURL']}">X</a>
    {/if}
    &ldquo;{$comment['message']}&rdquo; 
    <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['delta']}</span>
  {/capture}
  {$video['comments'][$i]['title'] = $title}
{/foreach}

{capture name="addCommentHTML" assign="addCommentHTML"}
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    <textarea rows="2" name="message" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="id" value="{$video['id']|escape:'url'}" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addComment = array()}
{$addComment['title'] = $addCommentHTML}
{$video['comments'][] = $addComment}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$video['comments']}


{include file="findInclude:common/templates/footer.tpl"}
