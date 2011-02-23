{capture name="banner" assign="banner"}
  <div class="banner nonfocal">
    <div id="ribbon">
      <span class="ribbonYear">10<span class="ribbonYearSup">th</span></span><br/><span class="ribbonDesc">Reunion</span><br/><span class="ribbonDate">May 27-29</span>
    </div>
    <h3>Harvard College Reunion</h3>
    <h2>John Smith</h2>
    <p>
      Class of 2001<br/>
      <span class="smallprint"><a href="#">Sign out</a> | <a href="/settings/">Settings</a></span>
    </p>
  </div>
{/capture}

{include file="findInclude:common/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/springboard.tpl" springboardItems=$modules springboardID="homegrid"}

<div id="social">
  <div class="links">
    <a class="facebookLink"><div class="wrapper">Harvard-Radcliff '01</div></a>
    <a class="twitterLink"><div class="wrapper">#hr10th</div></a>
  </div>
  <div class="recent twitter">
    <div class="chatbubble">
      big group going 2 John Harvard's in the Garage, everyone welcome
      <div class="info smallprint">Katarina Ragulin - 12 mins ago</div>
    </div>
    <div class="chatarrow">&lt;</div>
  </div>
</div>

{include file="findInclude:common/footer.tpl"}
