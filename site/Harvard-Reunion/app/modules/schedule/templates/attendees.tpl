{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$eventTitle}</h2>
  {$eventDate}
</div>

{include file="findInclude:common/templates/attendees.tpl" attendees=$attendees}

{include file="findInclude:common/templates/footer.tpl"}
