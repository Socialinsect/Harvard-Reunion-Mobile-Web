<div class="postNav" id="navbar2">
  {block name="postNavigation"}
    {if $post['prevURL']}
      <a id="prev" class="postControl" href="{$post['prevURL']}">
        <img src="/common/images/button-navbar2-prev.png" alt="prev"/>
      </a>
    {/if}
    <a id="comment" class="postControl" href="#commentscrolldown">
        <img src="/common/images/button-navbar2-comment.png" alt="comment"/>
      </a>
    <a id="bookmark" class="postControl{if $bookmarkStatus == 'on'} on{/if}" onclick="toggleBookmark('{$cookieName}', '{$post['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}');">
        <img class="bookmark" src="/common/images/button-navbar2-bookmark.png" alt="bookmark" />
        <img class="unbookmark" src="/common/images/button-navbar2-unbookmark.png" alt="unbookmark"/>
      </a>
    <a id="like" class="postControl{if $post['liked']} liked{/if}" href="{$post['likeURL']}">
        <img class="like" src="/common/images/button-navbar2-like.png" alt="like"/>
        <img class="unlike" src="/common/images/button-navbar2-unlike.png" alt="unlike"/>
      </a>
    {if $post['nextURL']}
      <a id="next" class="postControl" href="{$post['nextURL']}">
        <img src="/common/images/button-navbar2-next.png" alt="next"/>
      </a>
    {/if}
  {/block}
</div>

{$post['html']}

<div class="nonfocal">
  <div class="message">{$post['message']|escape}</div>
  <div class="smallprint">
    Uploaded {$post['when']['delta']} by 
    <a class="author" href="{$post['author']['url']}">{$post['author']['name']|escape}</a>
    {if $post['liked'] || $post['otherLikes']}
      <br/>
      {if $post['liked']}
        You {if $post['otherLikes']}and {/if}
      {/if}
      {if $post['otherLikes']}
        {$post['otherLikes']} {if $post['otherLikes'] > 1}people {else}person {/if}
      {/if}
      like{if !$post['liked'] && $post['otherLikes'] == 1}s{/if} this {$post['typeString']}
    {/if}
  </div>
</div>

<div id="autoupdateContainer">
  {include file="findInclude:common/templates/postdetailContent.tpl" post=$post}
</div>
  
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
