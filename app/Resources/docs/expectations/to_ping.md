#ToPing Expectation
The _toPing_ expectation expects the hostname or IP address given as the actual to respond to an ICMP ping.

##Example
```
Expect 8.8.8.8 toPing
```
This expectation will take any valid IP address or hostname as the actual value.

This expectation will ignore any value provided as the expected value.

##Configuration
```
expectations_toPing_timeout:                    2
expectations_toPing_unsatisfactory:             1
```
**expectations_toPing_timeout** (float) Time, in seconds, to wait for a ping response before timing out and marking as unmet. Defaults to 2.

**expectations_toPing_unsatisfactory** (float) Time, in seconds, to wait for a ping response before marking as unsatisfactory. Defaults to 1.

##Notes
On some systems, the transmission of an ICMP ping packet requires administrative rights.
