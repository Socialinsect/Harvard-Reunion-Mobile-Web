{extends file="findExtends:common/templates/postdetail.tpl"}

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
