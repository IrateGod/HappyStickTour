<?php

require_once 'Database.php';

class OsuApi {
  public function getBeatmap($beatmapId) {

  }

  public function getUser($userId) {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    $stmt = $db->prepare('SELECT id, username, avatar_url as avatarUrl, hit_accuracy as hitAccuracy, level, play_count as playCount, pp, rank, rank_history as rankHistory, best_score as bestScore, playstyle, join_date as joinDate, country, cache_update as cacheUpdate
      FROM osu_users
      WHERE id = :id OR username = :username');
    $stmt->bindValue(':id', $userId, PDO::PARAM_STR);
    $stmt->bindValue(':username', $userId, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
    $returnValue = new StdClass;
    if (!isset($rows[0]) || (isset($rows[0]) && (new DateTime($rows[0]->cacheUpdate))->diff(new DateTime())->d >= 3)) {
      $curl = curl_init();
      curl_setopt_array($curl, array(
          CURLOPT_SSL_VERIFYPEER => 0,
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_FOLLOWLOCATION => 1,
          CURLOPT_URL => 'https://osu.ppy.sh/users/' . $userId
        )
      );
      $html = curl_exec($curl);
      curl_close($curl);
      $dom = new DOMDocument();
      @$dom->loadHTML($html);
      $user = json_decode($dom->getElementById('json-user')->textContent);

      $returnValue->id = $user->id;
      $returnValue->username = $user->username;
      $returnValue->avatarUrl = $user->avatar_url;
      $returnValue->hitAccuracy = $user->allStatistics->osu->hit_accuracy;
      $returnValue->level = $user->allStatistics->osu->level->current;
      $returnValue->playCount = $user->allStatistics->osu->play_count;
      $returnValue->pp = $user->allStatistics->osu->pp;
      $returnValue->rank = $user->allStatistics->osu->rank->global;
      $returnValue->rankHistory = $user->allRankHistories->osu->data;
      $returnValue->bestScore = $user->allScoresBest->osu[0]->beatmapset->artist . ' - ' . $user->allScoresBest->osu[0]->beatmapset->title . ' [' . $user->allScoresBest->osu[0]->beatmap->version . ']';
      if (count($user->allScoresBest->osu[0]->mods) > 0) {
        $returnValue->bestScore .= ' +' . join(',', $user->allScoresBest->osu[0]->mods);
      }
      $returnValue->bestScore .= ' (' . $user->allScoresBest->osu[0]->pp . 'PP)';
      $returnValue->playstyle = join(' + ', $user->playstyle);
      $returnValue->joinDate = (new DateTime($user->join_date))->format('Y-m-d H:i:s');
      $returnValue->country = $user->country->code;
      $returnValue->cacheUpdate = date('Y-m-d H:i:s');

      if (!isset($rows[0])) {
        $stmt = $db->prepare('INSERT INTO osu_users (id, username, avatar_url, hit_accuracy, level, play_count, pp, rank, rank_history, best_score, playstyle, join_date, country, cache_update)
          VALUES (:id, :username, :avatar_url, :hit_accuracy, :level, :play_count, :pp, :rank, :rank_history, :best_score, :playstyle, :join_date, :country, NOW())');
        $stmt->bindValue(':id', $returnValue->id, PDO::PARAM_INT);
        $stmt->bindValue(':username', $returnValue->username, PDO::PARAM_STR);
        $stmt->bindValue(':avatar_url', $returnValue->avatarUrl, PDO::PARAM_STR);
        $stmt->bindValue(':hit_accuracy', $returnValue->hitAccuracy, PDO::PARAM_STR);
        $stmt->bindValue(':level', $returnValue->level, PDO::PARAM_INT);
        $stmt->bindValue(':play_count', $returnValue->playCount, PDO::PARAM_INT);
        $stmt->bindValue(':pp', $returnValue->pp, PDO::PARAM_STR);
        $stmt->bindValue(':rank', $returnValue->rank, PDO::PARAM_INT);
        $stmt->bindValue(':rank_history', join(',', $returnValue->rankHistory), PDO::PARAM_STR);
        $stmt->bindValue(':best_score', $returnValue->bestScore, PDO::PARAM_STR);
        $stmt->bindValue(':playstyle', $returnValue->playstyle, PDO::PARAM_STR);
        $stmt->bindValue(':join_date', $returnValue->joinDate, PDO::PARAM_STR);
        $stmt->bindValue(':country', $returnValue->country, PDO::PARAM_STR);
        $stmt->execute();
      } else {
        $stmt = $db->prepare('UPDATE osu_users
          SET username = :username, avatar_url = :avatar_url, hit_accuracy = :hit_accuracy, level = :level, play_count = :play_count, pp = :pp, rank = :rank, rank_history = :rank_history, best_score = :best_score, playstyle = :playstyle, join_date = :join_date, country = :country, cache_update = NOW()
          WHERE id = :id');
        $stmt->bindValue(':username', $returnValue->username, PDO::PARAM_STR);
        $stmt->bindValue(':avatar_url', $returnValue->avatarUrl, PDO::PARAM_STR);
        $stmt->bindValue(':hit_accuracy', $returnValue->hitAccuracy, PDO::PARAM_STR);
        $stmt->bindValue(':level', $returnValue->level, PDO::PARAM_STR);
        $stmt->bindValue(':play_count', $returnValue->playCount, PDO::PARAM_INT);
        $stmt->bindValue(':pp', $returnValue->pp, PDO::PARAM_STR);
        $stmt->bindValue(':rank', $returnValue->rank, PDO::PARAM_INT);
        $stmt->bindValue(':rank_history', join(',', $returnValue->rankHistory), PDO::PARAM_STR);
        $stmt->bindValue(':best_score', $returnValue->bestScore, PDO::PARAM_STR);
        $stmt->bindValue(':playstyle', $returnValue->playstyle, PDO::PARAM_STR);
        $stmt->bindValue(':join_date', $returnValue->joinDate, PDO::PARAM_STR);
        $stmt->bindValue(':country', $returnValue->country, PDO::PARAM_STR);
        $stmt->bindValue(':id', $returnValue->id, PDO::PARAM_INT);
        $stmt->execute();
      }
    } else {
      $returnValue = $rows[0];
    }
    
    return $returnValue;
  }

  public function getMatch($matchId) {

  }
}

?>