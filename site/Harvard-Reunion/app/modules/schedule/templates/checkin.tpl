{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <p>Check in to {$eventTitle}</p>
</div>

<div class="focal checkinForm">
  <form method="get" action="addCheckin">
    <textarea rows="3" name="message" id="messageText" placeholder="Add a message"></textarea>
    <input type="submit" value="Submit" />
    {foreach $hiddenArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>

{include file="findInclude:common/templates/footer.tpl"}
