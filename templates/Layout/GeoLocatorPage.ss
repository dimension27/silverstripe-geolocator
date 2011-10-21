<% require css(geolocator/css/GeoLocatorPage.css) %>
$Content
<ul class="nearest-results">
<% control Nearest %>
	<li>$MapContent</li>
<% end_control %>
</ul>
$Nearest.GoogleMap