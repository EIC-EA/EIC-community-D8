import React from 'react';
import svg from "../../../../../../../svg/svg";
import {getLikeContentEndpoint, getLikeContentStatusEndpoint} from "../../../Services/UrlHelper";
import axios from "axios";

class LikeLink extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            action: 'flag',
            allow: false
        }
    }
    componentDidMount() {
        this.likeContent = this.likeContent.bind(this);
        this.likeContentStatusUrl = getLikeContentStatusEndpoint(this.props.currentGroupId, this.props.nodeId)
        const self = this;

        axios.get(this.likeContentStatusUrl, {
            withCredentials: true,
        }).then(function (response) {
            self.setState({
                action: response.data.action,
                allow: true
            })
        }).catch(error => {
            if (error.response.status === 401) {
                self.setState({
                    allow: false
                })
            }
        });
    }

    likeContent(e) {
        e.preventDefault();
        const url = getLikeContentEndpoint(this.props.currentGroupId, this.props.nodeId, this.state.action);
        const self = this;

        axios.post(url, {
            withCredentials: true,
        }).then(function (response) {
            self.setState({
                action: self.state.action === 'flag' ? 'unflag' : 'flag',
            })
        })
    }

    render() {
        if (!this.state.allow)
            return <></>

        return (
            <div className="ecl-teaser__like">
                <a href="#" onClick={this.likeContent}>
          <span
              dangerouslySetInnerHTML={{__html: svg('like', 'ecl-icon ecl-icon--xs')}}
          />
                    &nbsp;
                    {this.state.action === 'flag' ? this.props.translations.like : this.props.translations.unlike}
                </a>
            </div>
        );
    }
}

export default LikeLink;
