<?php

# Template for pnp4nagios
# LM
# 20140306
# Global Vars
$servers = ".*app.*.stg.*";
$title = "Stage Application Servers";

$this->MACRO['TITLE']   = "$title Aggregation";
$this->MACRO['COMMENT'] = "$title Aggregation";

# Graph 0
# Example for aggregation with only one DS
$serv = "Number of threads";
$rpn_stack = ""; 	# empty RPN string
$counter= -1;		# loop counter starting by -1

# Get a list of services by regex
# Arg 1 = "Host Regex"
# Arg 2 = "Service Regex"
$services = $this->tplGetServices($servers, str_replace(" ","_",$serv));

# The Datasource Name for Graph 0
$ds_name[0] = "$title $serv";
$opt[0]     = "--title \"$title $serv\"";
$def[0]     = "";

# Iterate through the list of hosts
foreach($services as $key=>$val){
        
        # get the data for a given host/service
        $data = $this->tplGetData($val['host'],$val['service']);
        $host = rrd::cut($val['host'], 10);
        $def[0] .= rrd::def("var$key", $data['DS'][0]['RRDFILE'], $data['DS'][0]['DS'] );
	# Play around with uncommenting some of the following lines to get different styles of graphs
	# You'll be able to graph individual services as well as totals
	# $def[0] .= rrd::area("var$key", rrd::color($key), $host, "STACK");
	# $def[0] .= rrd::area("var$key", "#66ff66", $host, "STACK");
	# $def[0] .= rrd::gprint("var$key", array("LAST", "AVERAGE", "MAX"), "%3.2lf %s");
        $rpn_stack .= "var$key,";  # add the current key to the stack
        $counter++;
}
 
# After the foreach loop, create the rpn stack, then use it in a cdef
$rpn_stack .= str_repeat("+,", $counter);   # build the rpn stack for rrdtool "a0,a1,+,"
$rpn_stack = substr($rpn_stack, 0, -1);     # strip the last comma to avoid rpn underflow
$def[0] .= rrd::cdef("totals", $rpn_stack); # build a cdef using out rpn expression
$def[0] .= rrd::line2("totals", "#000000");
$def[0] .= rrd::gradient("totals", "#F5F5F5", "#FFCC66", "Agg $serv" );
$def[0] .= rrd::gprint("totals", array("LAST", "AVERAGE", "MAX"), "%.0lf %S");
 
$def[0] .= rrd::comment(" \\n");
$def[0] .= rrd::comment("This stat measures the number of threads");

# Next aggregation
# Graph 1
# Example with several DSs
# Vars for Graph 0
$serv = "CPU utilization";
$rpn_stack = ""; 	# empty RPN string
$counter= -1;		# loop counter starting by -1

# Get a list of services by regex
# Arg 1 = "Host Regex"
# Arg 2 = "Service Regex"

$services = $this->tplGetServices($servers, str_replace(" ","_",$serv));

# The Datasource Name for Graph 1
$ds_name[1] = "$title $serv";
$opt[1]     = "--title \"$title $serv\"";
$def[1]     = "";

# Iterate through the list of hosts
foreach($services as $key=>$val){
	# get the data for a given host/service
	$data = $this->tplGetData($val['host'],$val['service']);
	$host = rrd::cut($val['host'], 14);
	# Service has 3 DSs, retrieve all and add them using cdef
	$def[1] .= rrd::def("cpuuser$key", $data['DS'][0]['RRDFILE'], $data['DS'][0]['DS'] );
	$def[1] .= rrd::def("cpusystem$key", $data['DS'][1]['RRDFILE'], $data['DS'][1]['DS'] );
	$def[1] .= rrd::def("cpuwait$key", $data['DS'][2]['RRDFILE'], $data['DS'][2]['DS'] );
	$def[1] .= rrd::cdef("cputotal$key", "cpuuser$key,cpusystem$key,+,cpuwait$key,+"); 
	# Uncomment to graph per service as well as total
#	$def[1] .= rrd::area("cputotal$key", rrd::color($key), $host, "STACK");
#	$def[1] .= rrd::area("cputotal$key", "#66ff66", $host, "STACK");
#   	$def[1] .= rrd::gprint("cputotal$key", array("LAST", "AVERAGE", "MAX"), "%3.2lf %s");
	$rpn_stack .= "cputotal$key,"; 	# add the current key to the stack
	$counter++;
}

# After the foreach loop, create the rpn stack, then use it in a cdef
$rpn_stack .= str_repeat("+,", $counter);   # build the rpn stack for rrdtool "a0,a1,+,"
$rpn_stack = substr($rpn_stack, 0, -1);     # strip the last comma to avoid rpn underflow
$def[1] .= rrd::cdef("totals", $rpn_stack); # build a cdef using out rpn expression
$def[1] .= rrd::line2("totals", "#000000");
$def[1] .= rrd::gradient("totals", "#F5F5F5", "#FF9999", "Agg $serv" );
$def[1] .= rrd::gprint("totals", array("LAST", "AVERAGE", "MAX"), "%3.2lf %s %%");
$def[1] .= rrd::comment(" \\n");
$def[1] .= rrd::comment(" This stat measures the use of CPU in percentage values");

?>

