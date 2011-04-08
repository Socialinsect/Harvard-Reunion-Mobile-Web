<div class="nonfocal postNav" id="navbar2">
  {if $post['prevURL']}
    <a class="postControl" id="prev" href="{$post['prevURL']}"><span>Prev</span></a>
    <span class="textspacer"> | </span>
  {/if}
  <a class="postControl" id="comment" href="#commentscrolldown"><span>Comment</span></a>
  <span class="textspacer"> | </span>
  <a class="postControl" id="bookmark" class="{$bookmarkStatus}" href="{$bookmarkURL}" onclick="toggleBookmark('{$cookieName}', '{$post['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}'); return false;"><span>{if $bookmarkStatus == 'on'}Unbookmark{else}Bookmark{/if}</span></a>
  <span class="textspacer"> | </span>
  <a class="postControl" id="like" class="{if $post['liked']}liked{/if}" href="{$post['likeURL']}"><span>{if $post['liked']}Unlike{else}Like{/if}</span></a>  
  {if $post['nextURL']}
    <span class="textspacer"> | </span>
    <a class="postControl" id="next" href="{$post['nextURL']}"><span>Next</span></a>
  {/if}
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
    &ldquo;{$comment['message']}&rdquo; 
    <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['delta']}
    </span>
  {/capture}
  {$post['comments'][$i]['title'] = $title}
{/foreach}

{if count($post['comments'])}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$post['comments'] navlistID="listContainer" accessKey=false}
{/if}
  
<div class="focal fbPostForm">
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    <textarea rows="3" name="message" id="messageText" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" onclick="return validateTextInputForm('messageText', 'Please enter a comment for this Facebook post.');" />
    <input type="hidden" name="id" value="{$post['id']|escape:'url'}" />
    <input type="hidden" name="view" value="{$currentView}" />
    <input type="hidden" name="action" value="add" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>
