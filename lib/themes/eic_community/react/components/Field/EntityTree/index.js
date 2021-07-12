import React from 'react';
import {Accordion, AccordionSummary, FormControlLabel, Link, TextField} from "@material-ui/core";
import ExpandMoreIcon from '@material-ui/icons/ExpandMore';
import ArrowDropDownCircleRoundedIcon from '@material-ui/icons/ArrowDropDownCircleRounded';
import axios from "axios";
import Results from "./Result/Results";
import Search from "./Result/Search";
import Chip from '@material-ui/core/Chip';
import Card from '@material-ui/core/Card';
import CardContent from '@material-ui/core/CardContent';
import CircularProgress from "@material-ui/core/CircularProgress";

class EntityTree extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      results: [],
      values: JSON.parse(this.props.selectedTerms),
      total: 0,
      isLoadingMore: false,
      page: 0,
      length: this.props.length,
    }

    this.searchTerms = this.searchTerms.bind(this);
    this.addChip = this.addChip.bind(this);
    this.removeChip = this.removeChip.bind(this);
  }

  componentDidMount() {
    this.searchTerms();
  }

  searchTerms() {
    const self = this;
    const params = {
      offset: this.state.page * this.state.length,
      length: this.state.length,
      loadAll: this.props.loadAll,
    };

    this.setState({
      isLoadingMore: true
    });

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        let results = self.state.results;
        results = results.concat(response.data.terms);

        self.setState({
          results,
          total: response.data.total,
          page: self.state.page + 1,
          isLoadingMore: false
        })
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  fillDrupalInput() {
    const values = this.state.values.map(function (elem) {
      return `${elem.name} (${elem.tid})`;
    }).join(',');

    this.props.drupalInput.value = values;
    this.props.drupalInput.setAttribute('value', values);
  }

  addChip(value) {
    const selectedTerms = this.state.values;
    let alreadyExist = false;

    selectedTerms.forEach(selectedTerm => {
      if (parseInt(selectedTerm.tid) === parseInt(value.tid)) {
        alreadyExist = true;
      }
    });

    if (alreadyExist) {
      return;
    }

    const topLevelLength = selectedTerms.filter(function(item){
      return parseInt(item.parent) === 0;
    }).length;

    //Check that we have a matchLimit config (> 0), that the selected top level isn't higher than the config and the current value added is a top level item
    if (topLevelLength >= this.props.matchLimit && this.props.matchLimit !== 0 && parseInt(value.parent) === 0) {
      return;
    }

    selectedTerms.push(value);

    this.setState({
      values: selectedTerms
    });

    this.fillDrupalInput();
  }

  removeChip(valueToRemoved) {
    const values = this.state.values;
    const newValues = [];

    values.forEach(value => {
      if (parseInt(value.tid) !== parseInt(valueToRemoved)) {
        newValues.push(value);
      }
    });

    this.setState({
      values: newValues
    });

    this.fillDrupalInput();
  }

  render() {
    return (
      <div className="entity-tree">
        <Card>
          <CardContent>
            {this.state.values.map((value) => {
              return <Chip
                key={value.tid}
                label={value.name}
                color={parseInt(value.parent) === 0 ? 'primary' : 'default' }
                onDelete={() => this.removeChip(value.tid)}
              />
            })}
            <div style={{marginTop: "5px"}}>
              <i>Blue tags mean top level item</i>
            </div>
            <Search url="/api/terms-tree/search" addChip={this.addChip} />
            <p><b>{this.state.results.length} on {this.state.total} total results</b></p>
            {this.props.matchLimit > 0 && <p><i>You can select only <b>{this.props.matchLimit}</b> top-level items.</i></p>}
            <div className="entity-tree--options">
              <Accordion>
                <AccordionSummary
                  aria-label="Expand"
                  aria-controls="additional-actions1-content"
                  id="additional-actions1-header"
                  expandIcon={<ExpandMoreIcon/>}
                >
                  <b>Select a topic</b>
                </AccordionSummary>
                <Results
                  values={this.state.values}
                  url={this.props.url + '/children'}
                  results={this.state.results}
                  addChip={this.addChip}
                  removeChip={this.removeChip}
                  parent={0}
                />

                {this.state.isLoadingMore ?
                  <CircularProgress className="load-more-loading" size={20} /> :
                  !this.props.loadAll && <ArrowDropDownCircleRoundedIcon className="load-more" color="primary" onClick={this.searchTerms} />
                }
              </Accordion>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

}

export default EntityTree;
