import axios from 'axios'
import * as types from '../mutation-types'

// state
export const state = {
  tournmentStarted: false,
  over: 0,
  teams: [],
  matches: [],
  innings: [],
  standings: [],
  scorePerMatch: {},
  matchDetails: {},
  teamDetails: {},
  palyers: {},
  loading: '',
  selectedMatch: null
}

// getters
export const getters = {
  tournmentStarted: state => state.tournmentStarted,
  scorePerMatch: state => state.scorePerMatch,
  teamDetails: state => state.teamDetails,
  standings: state => state.standings,
  palyers: state => state.palyers,
  matches: state => state.matches,
  innings: state => state.innings,
  teams: state => state.teams,
  loading: state => state.loading,
  selectedMatch: state => state.selectedMatch,
  over: state => state.over
}

// mutations
export const mutations = {
  [types.MATCH_STARTED] (state, { matches, teams, palyers }) {
    state.tournmentStarted = true

    matches.forEach(row => {
      state.matchDetails[row.id] =  row
    })
    state.selectedMatch = matches[0].id

    teams.forEach(row => {
      state.teamDetails[row.id] =  row
    })

    palyers.forEach(row => {
      state.palyers[row.id] =  row
    })
  },

  [types.UPDATE_SCORE] (state, data) {
    // console.log('UPDATE_SCORE ', data)
    state.innings = [...state.innings, ...data]
    // console.log('nnnnnn ', data);

    state.loading = ''
    if (data.length == 0) {
      state.loading = 'Matches ends'
    } else {
      state.over = data[data.length -1] ? data[data.length -1].id : false
    }

    data.forEach((row, i) => {
      if (!state.scorePerMatch[row.match_id]) {
        state.scorePerMatch[row.match_id] = {
          team1: {
            team_id: null,
            name: '',
            runs: 0,
            wkts: 0,
            over: 0,
            rr: 0,
            win: false
          },
          team2: {
            team_id: null,
            name: '',
            runs: 0,
            wkts: 0,
            over: 0,
            rr: 0,
            win: false
          },
          rslt: '',
          innings: [],
          date: state.matchDetails[row.match_id].start_at
        }
      }
      state.scorePerMatch[row.match_id].innings.push(row)
    })

    for (var key in state.scorePerMatch) {
      if (state.scorePerMatch.hasOwnProperty(key)) {
        const innings = state.scorePerMatch[key].innings
        state.scorePerMatch[key].team1 = {
          team_id: null,
          name: '',
          runs: 0,
          wkts: 0,
          over: 0,
          rr: 0,
          win: false
        }
        state.scorePerMatch[key].team2 = {
          team_id: null,
          name: '',
          runs: 0,
          wkts: 0,
          over: 0,
          rr: 0,
          win: false
        }
        let out = 0
        innings.forEach((row, i) => {
          if (row.inning == 1) {
            state.scorePerMatch[key].team1.team_id = row.batting_team_id
            state.scorePerMatch[key].team1.name = state.teamDetails[row.batting_team_id].name
            state.scorePerMatch[key].team1.runs += row.result
            if (row.status == 'out') {
              // state.scorePerMatch[key].team1.runs += 1
            }
          } else {
            state.scorePerMatch[key].team2.team_id = row.batting_team_id
            state.scorePerMatch[key].team2.name = state.teamDetails[row.batting_team_id].name
            state.scorePerMatch[key].team2.runs += row.result
            if (row.status == 'out') {
              // state.scorePerMatch[key].team2.runs += 1
              out += 1
            }
          }
        })

        if (state.loading == 'Matches ends') {
          if (state.scorePerMatch[key].team1.runs > state.scorePerMatch[key].team2.runs) {
            const diff = state.scorePerMatch[key].team1.runs - state.scorePerMatch[key].team2.runs

            state.scorePerMatch[key].rslt = state.teamDetails[state.scorePerMatch[key].team1.team_id].name+'won by '+diff+ ' runs.'
          } else {
            const diff = 10 - out
            state.scorePerMatch[key].rslt = state.teamDetails[state.scorePerMatch[key].team2.team_id].name+'won by '+diff+ ' wickets.'
          }
        }

      }
    }

  },

  [types.MATCH_STANDING] (state, { standings }) {
    state.standings = standings
  },

  [types.UPDATE_SELECTED_MATCH] (state, payload) {
    state.selectedMatch = payload
  },

  [types.LOADING_START] (state) {
    state.loading = 'Loading...'
  }
}

// actions
export const actions = {
  saveToken ({ commit, dispatch }, payload) {
    commit(types.SAVE_TOKEN, payload)
  },

  async startTournment ({ commit }) {
    try {
      const { data } = await axios.put('/api/start/tournment/')
      commit(types.MATCH_STARTED, data.data)
    } catch (e) {
      console.log(e);
    }
  },

  async runOver ({ commit, state }) {
    try {
      commit(types.LOADING_START)
      const { data } = await axios.post('/api/tournment/runOver')
    } catch (e) {
      console.log(e);
    }
  },

  async getScore ({ commit, state }) {
    try {
      const { data } = await axios.get('/api/tournment/getScore/' + state.over)
      commit(types.UPDATE_SCORE, data)
    } catch (e) {
      console.log(e);
    }
  },

  async standings ({ commit }) {
    try {
      const { data } = await axios.get('/api/tournment/standings')

      commit(types.MATCH_STANDING, { standings: data.data })
    } catch (e) {
      console.log(e);
    }
  },

  async matches ({ commit }) {
    try {
      const { data } = await axios.get('/api/tournment/matches/1')

      // commit(types.MATCH_STANDING, { standings: data.data })
    } catch (e) {
      console.log(e);
    }
  },

  updateUser ({ commit }, payload) {
    commit(types.UPDATE_USER, payload)
  },

  selectMatch ({ commit }, payload) {
    commit(types.UPDATE_SELECTED_MATCH, payload)
  }

}
