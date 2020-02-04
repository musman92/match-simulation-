<template>
  <div class="row mt-2 w-100 ">
    <div class="col-md-12">
      <h1 class="text-center">Match Simulation
        <button type="button"  v-if="tournmentStarted == false" @click="startTournment" class="btn btn-sm btn-secondary">Start</button>
        <span>{{loading}}</span>
      </h1>
      <hr/>
    </div>
    <div class="row col-md-12">
      <div class="col-md-7">
        <card class="settings-card rounded-lg p-1">
          <h1 class="text-primary">Result</h1>
          <div>
            <ul class="list-group">
              <li v-for="(row, match_id, i) in scorePerMatch" :key="i" class="list-group-item"
                @click="selectMatch(match_id)"
                :class="{'list-group-item-action active':match_id == selectedMatch }">
                <div class="d-flex">
                  <div class="pr-2">{{row.date}}</div>
                  <div class="d-flex flex-column">
                    <div class="d-flex">
                      <span>{{row.team1.name}}</span>
                      <div v-html="calacRun(row.innings, 1)"></div>
                    </div>
                    <div class="d-flex">
                      <span>{{row.team2.name}}</span>
                      <div v-html="calacRun(row.innings, 2)"></div>
                    </div>
                  </div>
                </div>
                <div>{{row.rslt}}</div>
              </li>
            </ul>
          </div>
        </card>
      </div>
      <div class="col-md-5">
        <card class="settings-card rounded-lg p-1">
          <h1 class="text-primary">Standing</h1>
          <div class="table-responsive">
            <table class="table table-sm table-striped">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Team</th>
                  <th scope="col">MP</th>
                  <th scope="col">W</th>
                  <th scope="col">L</th>
                  <th scope="col">N/R</th>
                  <th scope="col">R</th>
                  <th scope="col">NRR</th>
                  <th scope="col">pts</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in standings" :key="i">
                  <th scope="row">{{i+1}}</th>
                  <td>{{row.name}}</td>
                  <td>{{row.total}}</td>
                  <td>{{row.wins}}</td>
                  <td>{{row.lose}}</td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td>{{row.wins*2}}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </card>
        <card class="settings-card rounded-lg p-1">
          <h1 class="text-primary">Scorecard</h1>
          <ul class="nav nav-tabs">
            <li class="nav-item">
              <a class="nav-link " :class="{'active': selectedInnings == 1}" @click="selectedInnings = 1" href="#">First</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" :class="{'active': selectedInnings == 2}" @click="selectedInnings = 2" href="#">Second</a>
            </li>
          </ul>
          <div>
            <table class="table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Batsman</th>
                  <th scope="col">Status</th>
                  <th scope="col">R</th>
                  <th scope="col">B</th>
                  <th scope="col">Min</th>
                  <th scope="col">4s</th>
                  <th scope="col">6s</th>
                  <th scope="col">S/R</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, id, i) in getBattingList(selectedInnings)" :key="i">
                  <th scope="row">{{(Number(i)+1)}}</th>
                  <td>{{row.name}}</td>
                  <td>{{row.status}}</td>
                  <td>{{row.runs}}</td>
                  <td>{{row.balls}}</td>
                  <td>{{row.min}}</td>
                  <td>{{row.four}}</td>
                  <td>{{row.six}}</td>
                  <td>{{row.balls == 0 ? 0 : (row.runs/row.balls).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
            <table class="table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Bowler</th>
                  <th scope="col">O</th>
                  <th scope="col">M</th>
                  <th scope="col">R</th>
                  <th scope="col">W</th>
                  <th scope="col">E/R</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, id, i) in getBowlingList(selectedInnings)" :key="i">
                  <th scope="row">{{(Number(i)+1)}}</th>
                  <td>{{row.name}}</td>
                  <td>{{row.over}}</td>
                  <td>{{row.m}}</td>
                  <td>{{row.runs}}</td>
                  <td>{{row.w}}</td>
                  <td>{{row.over == 0 ? 0 : (row.runs/row.over).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </card>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  layout: 'basic',

  data: () => ({
    timeout: null,
    selectedInnings: 1,
  }),

  computed: mapGetters({
    tournmentStarted: 'match/tournmentStarted',
    scorePerMatch: 'match/scorePerMatch',
    standings: 'match/standings',
    over: 'match/over',
    selectedMatch: 'match/selectedMatch',
    palyers: 'match/palyers',
    loading: 'match/loading'
  }),

  methods: {
    async startTournment () {
      await this.$store.dispatch('match/startTournment')
      this.loopfn()
    },

    loopfn () {
      if (this.loading != 'Matches ends') {
        this.timeout = setTimeout(() => {
          this.loopfn()
        }, 5000)
        this.run()
      } else {
        window.clearTimeout(this.timeout)
      }
    },

    async run () {
      await this.$store.dispatch('match/runOver')
      await this.$store.dispatch('match/standings')
      await this.$store.dispatch('match/matches')
      await this.$store.dispatch('match/getScore')
    },

    calacRun (list, inning) {
      const filtered = list.filter(row => row.inning == inning)
      let score = 0; let out = 0; let over = 0
      filtered.forEach(row => {
        score += row.result
        if (row.status == 'out') {
          out += 1
        }
        over = row.ball
      });
      const rr = over == 0 ? '' : (score / over).toFixed(2)
      return `<span class="mx-2">${score}/${out} (${over})</span> <span class="mx-2"> ${rr} </span>`
    },

    getBattingList (inning) {
      if(!this.selectedMatch || !this.scorePerMatch[this.selectedMatch]) {
        return []
      }

      const filtered = this.scorePerMatch[this.selectedMatch].innings.filter(row => row.inning == inning)
      let list = [];
      let stat = {}
      filtered.forEach(row => {
        if(!stat[row.batsman_id]) {
          stat[row.batsman_id] = {
            name: this.palyers[row.batsman_id].name,
            runs: 0,
            balls: 0,
            min: '',
            four: 0,
            six: 0,
            sr: 0,
            status: 'not out',
          }
        }
        stat[row.batsman_id].runs += row.result
        stat[row.batsman_id].balls += 1
        if(row.result == 4) {
          stat[row.batsman_id].four += 1
        }
        if(row.result == 6) {
          stat[row.batsman_id].six += 1
        }
        if(row.status == 'out') {
          stat[row.batsman_id].status = row.status2
        }
      })
      return stat
    },

    getBowlingList (inning) {
      if(!this.selectedMatch || !this.scorePerMatch[this.selectedMatch]) {
        return []
      }

      const filtered = this.scorePerMatch[this.selectedMatch].innings.filter(row => row.inning == inning)
      let stat = {}
      let prev = null
      filtered.forEach(row => {
        if (!stat[row.bowler_id]) {
          stat[row.bowler_id] = {
            name: this.palyers[row.bowler_id].name,
            over: 0,
            m: 0,
            runs: 0,
            w: 0
          }
        }
        if(prev != row.bowler_id) {
          stat[row.bowler_id].over += 1
        }
        stat[row.bowler_id].runs += row.result
        if (row.status == 'out') {
          stat[row.bowler_id].w += 1
        }
        prev = row.bowler_id
      })
      return stat
    },
    selectMatch (match_id) {
      this.$store.dispatch('match/selectMatch', match_id)
    }
  }
}
</script>

<style>
.settings-card .card-body {
  padding: 0;
}
</style>
