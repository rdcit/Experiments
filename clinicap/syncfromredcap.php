<?php
include 'html_include/html_top.php';
include 'html_include/menu.php';
include 'config/config.php';
include_once 'classes/OpenClinicaSoapWebService.php';
include_once 'classes/RedCapToOpenClinicaMap.php';

?>
<!--main content starts-->
<script type="text/javascript">
        jQuery( document ).ready(function() {

            });
        </script>


<div class="content">
	<div class="main-content">

			<div class="row">

			<div class="col-sm-6 col-md-6">

				<div id="ocStudiesDiv">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">
							RedCap MetaData: 
						</div>
						<div id="ocStudiesData">
							<?php 
							
							
							
							$file = 'uploads/rcsource.xml';
							//print_r(get_declared_classes());
							$mapObj = new RedCapToOpenClinicaMap($file);
							
							$mapObj->loadSourceMetaData();
							
							var_dump($mapObj->getEventsSource());
							echo '<br/><br/>';
							
							?>
						</div>
						<div id="studyMessageDiv"></div>
					</div>

				</div>
			</div>

			<div class="col-sm-6 col-md-6">
				<div id="rcProjectsDiv">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">OpenClinica MetaData</div>
						<div id="rcProjectsData">
													<?php 
							$mapObj->loadTargetMetaData();
							
							var_dump($mapObj->getEventsTarget());
							echo '<br/><br/>';
							?>
							
						</div>
						<div id="projectMessageDiv"></div>
					</div>
				</div>
			</div>


		</div>
	
	
	
	
	
	

		<div class="row">
			<div id="actions" class="col-sm-6 col-md-6">
				<div class="panel panel-default">
				<?php 
				
				$mapObj->createSyncDataMap();
				var_dump($mapObj->getSyncDataMap());
				
				
				?>
				</div>

			</div>

			<div id="pdetails" class="col-sm-6 col-md-6"></div>
		</div>
		<div class="row" id="debug">
<?php

?>
	</div>

	</div>

	<!--main content ends-->
<?php
include 'html_include/html_bottom.php';