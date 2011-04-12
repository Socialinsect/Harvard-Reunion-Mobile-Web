<div class="postNav" id="navbar2">
  {block name="postNavigation"}
    {if $post['prevURL']}
      <a class="postControl" id="prev" href="{$post['prevURL']}"><div></div></a>
    {/if}
    <a class="postControl" id="comment" href="#commentscrolldown"><div></div></a>
    <a class="postControl" id="bookmark" class="{$bookmarkStatus}" href="{$bookmarkURL}" onclick="toggleBookmark('{$cookieName}', '{$post['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}'); return false;"><div></div></a>
    <a class="postControl{if $post['liked']} liked{/if}" id="like" href="{$post['likeURL']}"><div></div></a>
    {if $post['nextURL']}
      <a class="postControl" id="next" href="{$post['nextURL']}"><div></div></a>
    {/if}
  {/block}
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
    {block name="comment"}
      &ldquo;{$comment['message']}&rdquo; 
      <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['delta']}</span>
    {/block}
  {/capture}
  {$post['comments'][$i]['title'] = $title}
{/foreach}

{if count($post['comments'])}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$post['comments'] navlistID="listContainer" accessKey=false}
{/if}
  
<div class="focal fbPostForm">
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    {block name="formelements"}
      <textarea rows="3" name="message" id="messageText" placeholder="Add a comment"></textarea>
      <input type="submit" value="Submit" onclick="return validateTextInputForm('messageText', 'Please enter a comment for this Facebook post.');" />
    {/block}
    <input type="hidden" name="id" value="{$post['id']|escape:'url'}" />
    <input type="hidden" name="view" value="{$currentView}" />
    <input type="hidden" name="action" value="add" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>
