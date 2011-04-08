{extends file="findExtends:common/templates/postdetail.tpl"}

{block name="postNavigation"}
  {if $post['prevURL']}
    <a href="{$post['prevURL']}">Prev</a> | 
  {/if}
  <a href="#commentscrolldown">Comment</a> | 
  <a href="{$bookmarkURL}">{if $bookmarkStatus == 'on'}Unbookmark{else}Bookmark{/if}</a> | 
  <a href="{$post['likeURL']}">{if $post['liked']}Unlike{else}Like{/if}</a>  
  {if $post['nextURL']}
     | <a href="{$post['nextURL']}">Next</a>
  {/if}
{/block}

{block name="comment"}
  "{$comment['message']}"
  <br/>
  <span class="smallprint"> - {$comment['author']['name']}, {$comment['when']['delta']}</span>
  <br/>
{/block}

{block name="formelements"}
  <label for="messageText">Add a comment</label><br/>
  <textarea rows="3" name="message" id="messageText"></textarea><br/>
  <input type="submit" value="Submit" />
{/block}
