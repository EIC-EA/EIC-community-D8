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
      hasError: false,
    }

    this.search = this.search.bind(this);
    this.searchTerms = this.searchTerms.bind(this);
    this.searchUsersInvite = this.searchUsersInvite.bind(this);
    this.addChip = this.addChip.bind(this);
    this.removeChip = this.removeChip.bind(this);
    this.submitForm = this.submitForm.bind(this)
    this.firstEl = React.createRef();
  }

  componentDidMount() {
    document.addEventListener('click', this.submitForm)
    this.search();
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.submitForm)
  }

  submitForm(e) {
    if(e.target.name !== 'op') {
      return
    }

    const hasError = this.state.values.length === 0;

    this.setState({
      hasError: hasError,
    })

    // If field is required and has no values scroll to it
    if (hasError && 1 === this.props.isRequired) {
      this.firstEl.current.scrollIntoView();
    }
  }

  search() {
    if ('invite' === this.props.targetBundle) {
      this.searchUsersInvite();
    } else {
      this.searchTerms();
    }
  }

  searchUsersInvite() {
    const self = this;
    const params = {
      offset: 50,
      length: this.state.length,
      loadAll: this.props.loadAll,
      targetBundle: this.props.targetBundle,
      targetEntity: this.props.targetEntity,
      ignoreCurrentUser: this.props.ignoreCurrentUser,
      page: this.state.page + 1,
      current_group: this.props.group
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
        const users = response.data.response.docs;

        for (const [key, value] of Object.entries(users)) {
          results.push({
            'tid': value.its_user_id,
            'level': 0,
            'parents': -1,
            'depth': 0,
            'name': value.ss_global_fullname,
            'weight': 0,
          });
        }

        self.setState({
          results,
          total: response.data.response.numFound,
          page: self.state.page + 1,
          isLoadingMore: false
        })
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  searchTerms() {
    const self = this;
    const params = {
      offset: this.state.page * this.state.length,
      length: this.state.length,
      loadAll: this.props.loadAll,
      targetBundle: this.props.targetBundle,
      targetEntity: this.props.targetEntity,
      ignoreCurrentUser: this.props.ignoreCurrentUser,
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

    // If the disable top select option is enable and parent was selected, do not check the option
    if (this.props.disableTop === 1 && parseInt(value.parent) === 0) {
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
      values: selectedTerms,
      hasError: selectedTerms.length === 0,
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
      <div className="entity-tree" ref={this.firstEl}>
        {this.state.hasError && 1 === this.props.isRequired &&
        <div className={'messages messages--error'}>
          <div className="alert">{this.props.translations.required_field}</div>
        </div>
        }
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
              disableTop={this.props.disableTop}
              page={this.state.page}
              group={this.props.group}
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
                  disableTop={this.props.disableTop}
                />

                {this.state.isLoadingMore ?
                  <CircularProgress className="load-more-loading" size={20} /> :
                  !this.props.loadAll && this.state.results.length < this.state.total && <ArrowDropDownCircleRoundedIcon className="load-more" color="primary" onClick={this.searchUsersInvite} />
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
