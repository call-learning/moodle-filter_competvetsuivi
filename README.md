## Compet Vet Suivi Filter

[![Build Status](https://travis-ci.org/call-learning/moodle-filter_competvetsuivi.svg?branch=master)](https://travis-ci.org/call-learning/moodle-filter_competvetsuivi)

Will embbed CompetVetSuivi (see https://github.com/call-learning/moodle-local_competvetsuivi) graph types anywhere it can be done via text Filtering.

For now two types of graphs can be embedded:
* Student results
* UE/UC vs Competencies

Warning: due to current performance, it is advised to put only one tag on a page.

### Embedding user results

In a text editor within Moodle, go in HTML mode and enter the following tag:

````
[competvetsuivi userid=<userid> type="studentprogress"][/competvetsuivi]
````


### Embedding UC vs Competency Graph

In a text editor within Moodle, go in HTML mode and enter the following tag:

````
[competvetsuivi uename='UC54' type="ucdetails"][/competvetsuivi]
````

Or this one for the Doghnut graph:

````
[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucsummary"][/competvetsuivi]
````