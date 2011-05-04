{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div class="focal fbPostForm">
    <form method="get" action="add">
      <a class="scrolllink" name="postscrolldown"> </a>
      {block name="facebookComment"}
        <textarea rows="3" name="message" id="messageText" placeholder="Share an update with the {$groupName} group"></textarea>
        <input type="submit" value="Share" onclick="_gaq.push(['_trackEvent', '{$smarty.const.GA_EVENT_CATEGORY}', 'Facebook Post', '{$groupName|escape:'javascript'}']); return validateTextInputForm('messageText', 'Please enter a message to post to the Facebook group.');" />
      {/block}
      <input type="hidden" name="type" value="facebook" />
      {foreach $breadcrumbSamePageArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    </form>
  </div>
  
  <div id="autoupdateContainer">
    {include file="findInclude:modules/$moduleID/templates/facebookContent.tpl" posts=$posts}
  </div>
  
{block name="facebookFooter"}
  <div class="nonfocal">
    <span class="smallprint">Signed in as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
  <div class="nonfocal">
    <span class="smallprint">View {$groupName} at <a href="{$groupURL}">facebook.com</a></span>
  </div>
{/block}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
