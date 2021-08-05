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
      targetBundle: this.props.targetBundle,
      targetEntity: this.props.targetEntity,
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
        const newTerms = response.data.terms;

        for (const [key, value] of Object.entries(newTerms)) {
          results.push(value);
        }

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

  fillDrupalInput(values) {
    values = values.map(function (elem) {
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

    this.fillDrupalInput(selectedTerms);
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

    this.fillDrupalInput(newValues);
  }

  render() {
    return (
      <div className="entity-tree">
        <Card>
          <CardContent>
            <p><b>{this.props.translations.your_values}</b></p>
            {this.state.values.map((value) => {
              return <Chip
                key={value.tid}
                label={value.name}
                color={'primary'}
                onDelete={() => this.removeChip(value.tid)}
              />
            })}
            <Search
              targetEntity={this.props.targetEntity}
              targetBundle={this.props.targetBundle}
              values={this.state.values}
              url={this.props.urlSearch}
              addChip={this.addChip}
              translations={this.props.translations}
            />
            <div className="entity-tree--options">
              <Accordion>
                <AccordionSummary
                  aria-label="Expand"
                  expandIcon={<ExpandMoreIcon/>}
                >
                  <b>{this.props.translations.select_value}</b>
                </AccordionSummary>
                <Results
                  values={this.state.values}
                  url={this.props.url + '/children'}
                  results={this.state.results}
                  addChip={this.addChip}
                  removeChip={this.removeChip}
                  parent={0}
                  targetEntity={this.props.targetEntity}
                  targetBundle={this.props.targetBundle}
                />

                {this.state.isLoadingMore ?
                  <CircularProgress className="load-more-loading" size={20} /> :
                  !this.props.loadAll && this.state.results.length < this.state.total && <ArrowDropDownCircleRoundedIcon className="load-more" color="primary" onClick={this.searchTerms} />
                }
              </Accordion>
            </div>
            <p><b>{this.state.results.length}/{this.state.total}</b></p>
            {this.props.matchLimit > 0 && <p><i dangerouslySetInnerHTML={{__html: this.props.translations.match_limit}} /></p>}
          </CardContent>
        </Card>
      </div>
    );
  }

}

export default EntityTree;
