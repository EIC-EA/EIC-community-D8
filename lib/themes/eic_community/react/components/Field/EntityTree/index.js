import React from 'react';
import {Accordion, AccordionSummary, FormControlLabel, TextField} from "@material-ui/core";
import ExpandMoreIcon from '@material-ui/icons/ExpandMore';
import axios from "axios";
import Results from "./Result/Results";
import Search from "./Result/Search";
import Chip from '@material-ui/core/Chip';
import Card from '@material-ui/core/Card';
import CardContent from '@material-ui/core/CardContent';

class EntityTree extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      results: {},
      values: [],
    }

    this.addChip = this.addChip.bind(this);
    this.removeChip = this.removeChip.bind(this);
  }

  componentDidMount() {
    this.searchTerms();
  }

  searchTerms(searchText = '', parent = 0) {
    const self = this;
    const params = {
      parent_term: parent
    };

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        self.setState({
          results: response.data.terms,
        })
      })
      .catch(function (error) {
      })
  }

  addChip(value) {
    console.log(value);
    const values = this.state.values;
    values.push(value);

    this.setState({
      values
    });
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
  }

  render() {
    return (
      <div className="entity-tree">
        <Card>
          <CardContent>
            {this.state.values.map((value) => {
              return <Chip
                label={value.name}
                color="primary"
                onDelete={() => this.removeChip(value.tid)}
              />
            })}
            <Search url="/api/terms-tree/search"/>
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
                />
              </Accordion>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

}

export default EntityTree;
