import React from 'react';
import {Accordion, AccordionSummary, FormControlLabel, FormLabel} from "@material-ui/core";
import ExpandMoreIcon from "@material-ui/icons/ExpandMore";
import Checkbox from "@material-ui/core/Checkbox";
import Results from "./Results";
import axios from "axios";
import CircularProgress from '@material-ui/core/CircularProgress';

class ResultItem extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      expanded: false,
      children: {},
      isLoadingData: false,
      isLoaded: false,
      parent: this.props.term ? parseInt(this.props.term.tid) : 0
    };

    this.handleChange = this.handleChange.bind(this);
    this.onCheck = this.onCheck.bind(this);
    this.isActive = this.isActive.bind(this);
    this.updateChip = this.updateChip.bind(this);
  }

  onCheck(e) {
    const checked = e.target.checked;
    this.updateChip(checked, false);
  }

  updateChip(checked, isAutoSelectParent) {
    if (checked) {
      this.props.addChip({name: this.props.term.name, tid: this.props.term.tid, parent: parseInt(this.props.term.parents[0])});
    } else {
      !isAutoSelectParent ? this.props.removeChip(this.props.term.tid) : null;
    }

    this.props.selectParent(checked);
  }

  handleChange(hasChildren) {
    if (!hasChildren) {
      return;
    }

    if (!this.state.isLoaded) {
      this.getChildren();
    } else {
      this.setState({
        expanded: !this.state.expanded
      })
    }
  }

  getChildren() {
    const self = this;

    this.setState({
      isLoadingData: true
    });

    const params = {
      parent_term: this.state.parent,
      depth: 1,
      level: this.props.term.level,
      targetEntity: this.props.targetEntity,
      targetBundle: this.props.targetBundle,
    };

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        self.setState({
          children: response.data.terms,
          isLoaded: true,
          expanded: !self.state.expanded,
          isLoadingData: false
        })
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  isActive() {
    let isActive = false;

    this.props.values.forEach(value => {
      if (parseInt(value.tid) === parseInt(this.props.term.tid)) {
        isActive = true;
      }
    });

    return isActive;
  }

  render() {
    const term = this.props.term;
    const hasChildren = term.children && term.children.length !== 0;
    const level = term.level || 0;
    return <Accordion square expanded={this.state.expanded} onChange={(e) => this.handleChange(hasChildren, term.parent)}>
      <AccordionSummary
        aria-label="Expand"
        expandIcon={hasChildren ? <ExpandMoreIcon/> : ''}
      >
        {this.props.disableTop === 1 && level === 0 ?
          <FormLabel
            style={{marginLeft: (level * 20) + 'px'}}
            className="entity-tree--options--label"
            key={term.tid}
          >{term.name}</FormLabel>
          :
          <FormControlLabel
            style={{marginLeft: (level * 20) + 'px'}}
            className="entity-tree--options--label"
            key={term.tid}
            control={<Checkbox
              name={term.tid.toString()}
              onChange={this.onCheck}
              color="primary"
              checked={this.isActive()}
            />}
            label={term.name}
          />
        }
        {this.state.isLoadingData && <CircularProgress className="loader" size={20} />}
      </AccordionSummary>
      {hasChildren && this.state.children && <Results
        url={this.props.url}
        results={this.state.children}
        addChip={this.props.addChip}
        removeChip={this.props.removeChip}
        updateChip={this.updateChip}
        values={this.props.values}
        parent={term}
        targetEntity={this.props.targetEntity}
        targetBundle={this.props.targetBundle}
      />}
    </Accordion>
  }

}

export default ResultItem;
