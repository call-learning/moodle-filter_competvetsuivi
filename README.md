## Compet Vet Suivi Filter

Will embbed CompetVetSuivi graph types anywhere it can be done via text Filtering.

For now two types of graphs can be embedded:
* Student results
* UE/UC vs Competencies

Warning: due to current performance, it is advised to put only one tag on a page.

### Embedding user results

In a text editor within Moodle, go in HTML mode and enter the following tag:

````
<competvetsuivi userid=<userid> type="studentprogress"></competvetsuivi>
````


### Embedding UC vs Competency Graph

In a text editor within Moodle, go in HTML mode and enter the following tag:

````
<competvetsuivi uename='UC54' type="ucoverview"></competvetsuivi>
````
