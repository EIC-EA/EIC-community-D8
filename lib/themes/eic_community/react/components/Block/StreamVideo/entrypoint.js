import React from 'react';
import ReactDOM from 'react-dom';
import StreamVideo from './index';

const element = document.getElementById('react-stream-video-block');
const video = element.dataset.videoTitle;

ReactDOM.render(<StreamVideo video={video} />, element);
