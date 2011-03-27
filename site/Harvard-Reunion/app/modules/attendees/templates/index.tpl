{include file="findInclude:common/templates/header.tpl"}

<ul>
{foreach $attendees as $attendee}
  <li>{$attendee['first_name']} {$attendee['last_name']}</li>
{/foreach}
</ul>

{include file="findInclude:common/templates/footer.tpl"}
